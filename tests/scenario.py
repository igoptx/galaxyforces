#!/usr/bin/env python3
"""Cenário de jogo completo com dois jogadores, verificando o estado na base de dados.

Registo, colónias, construção, recursos por ciclo, pesquisa, produção, academia,
mensagens, clã, viagem, banco e um ataque de um jogador ao outro. O relógio do jogo
é avançado recuando as colunas de tempo na base de dados.

    python3 tests/scenario.py                # milkyway
    python3 tests/scenario.py andromeda

Cria dois jogadores novos em cada execução (não apaga nada).
"""
import argparse, datetime, random, sys
from lib import Player, parse, php_errors, sql, text

UNIVERSES = {  # planeta inicial, outro planeta human na mesma galáxia, planeta com banco, planeta com Clan Hall
    "milkyway": ("mulahay", "earth", "ben", "mouse"),
    "andromeda": ("horus", "gaja", "gaja", "lira"),
}

ap = argparse.ArgumentParser(description=__doc__.split("\n")[0])
ap.add_argument("universe", nargs="?", default="milkyway", choices=UNIVERSES)
ap.add_argument("--base", help="URL do universo (por omissão http://<universo>.localhost:8088)")
ap.add_argument("--db", help="base de dados (por omissão galaxy_<universo>)")
ap.add_argument("--service", help="serviço docker compose do web (por omissão web-<universo>)")
args = ap.parse_args()
UNI = args.universe
BASE = args.base or f"http://{UNI}.localhost:8088"
DB = args.db or f"galaxy_{UNI}"
SERVICE = args.service or f"web-{UNI}"
START, OTHER, BANK, CLANHALL = UNIVERSES[UNI]
T0 = datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
ok = fail = 0


def check(name, cond, detail=""):
    global ok, fail
    if cond: ok += 1; print(f"  OK    {name}")
    else: fail += 1; print(f"  FALHA {name}  {detail}")


def one(q):
    r = sql(DB, q)
    return r[0][0] if r and r[0] else None


def col(owner, c): return one(f"select `{c}` from galaxy_colonies where owner='{owner}'")
def usr(login, c): return one(f"select `{c}` from galaxy_users where login='{login}'")
def now(): return int(one("select max(thicks) from galaxy_colonies"))


def advance(p, n):
    """Avança n ciclos do jogador. O motor processa no máximo 10 ciclos por pedido e
    depois salta para o presente, por isso o relógio recua em blocos de 10."""
    L = p.login_
    while n > 0:
        k = min(10, n); n -= k
        for q in (f"update galaxy_colonies set thicks=thicks-{k} where owner='{L}'",
                  f"update galaxy_users set thicks=thicks-{k}, time=if(time>0,time-{k},0) where login='{L}'",
                  f"update galaxy_buildings set begin=begin-{k} where login='{L}'",
                  f"update galaxy_researches set begin=begin-{k} where login='{L}'",
                  f"update galaxy_productions set begin=begin-{k} where login='{L}'",
                  f"update galaxy_exploration set begin=begin-{k} where login='{L}'",
                  f"update galaxy_attacks set begin=begin-{k}, time=time-{k} where login='{L}'"):
            sql(DB, q)
        p.get("control.php")


tag = random.randint(1000, 9999)
A = Player(BASE, f"ataca{tag}", "segredo1")
B = Player(BASE, f"defende{tag}", "segredo2")

print(f"== {UNI}: registo e login")
for p in (A, B):
    p.register(p.login_ + "@example.test")
    check(f"registo de {p.login_}", usr(p.login_, "login") == p.login_)
    p.login(); check(f"login de {p.login_}", True)

print("== colónias")
A.post("colony.php", action="create", name=f"Terra{tag}", planet=START)
B.post("colony.php", action="create", name=f"Marte{tag}", planet=OTHER)
for p in (A, B): check(f"colónia de {p.login_} criada", col(p.login_, "name") is not None)
check("créditos iniciais 50000", usr(A.login_, "credits") == "50000", usr(A.login_, "credits"))
check("recursos iniciais human (metal 300, colonos 5)", (col(A.login_, "metal"), col(A.login_, "colonists")) == ("300", "5"),
      (col(A.login_, "metal"), col(A.login_, "colonists")))

# acelerar: recursos e edifícios base para ter acesso a tudo
for p in (A, B):
    sql(DB, f"update galaxy_colonies set factory=3, laboratory=3, flats=10, barracks=5, academy=1, spacedepot=1, metal=500000, energy=500000, "
            f"silicon=50000, food=500000, colonists=500, scientists=100, crystals=500, militarytechnology=1, hawktechnology=1 where owner='{p.login_}'")
    sql(DB, f"update galaxy_users set credits=2000000, level=8, exp=30000, mp=1000, mpmax=1000, hp=1000, hpmax=1000 where login='{p.login_}'")

print("== construção")
before = int(col(A.login_, "flats"))
A.post("build.php", action="build", name="flats", amount="2")
t = one(f"select time from galaxy_buildings where login='{A.login_}' and name='flats'")
check("construção em fila", t is not None)
check("recursos gastos na construção", float(col(A.login_, "metal")) < 500000, col(A.login_, "metal"))
advance(A, int(t or 0) + 2)
check("construção concluída (+2 flats)", int(col(A.login_, "flats")) == before + 2, f"{before} -> {col(A.login_, 'flats')}")
check("fila de construção vazia", one(f"select count(*) from galaxy_buildings where login='{A.login_}'") == "0")

print("== recursos por ciclo")
e0, f0 = float(col(A.login_, "energy")), float(col(A.login_, "food"))
advance(A, 5)
snap = sql(DB, f"select energy, silicon, metal, food, colonists, scientists, flats from galaxy_colonies where owner='{A.login_}'")[0]
print("  recursos após construção + 5 ciclos:", ", ".join(snap))
check("energia/comida mudam com o tempo", (float(col(A.login_, "energy")), float(col(A.login_, "food"))) != (e0, f0), f"{e0},{f0}")

print("== pesquisa")
A.get("research.php", action="initiate", name="bxtechnology")
t = one(f"select time from galaxy_researches where login='{A.login_}'")
check("pesquisa iniciada", t is not None)
advance(A, int(t or 0) + 2)
check("pesquisa concluída (bxtechnology=1)", col(A.login_, "bxtechnology") == "1", col(A.login_, "bxtechnology"))

print("== produção de unidades")
A.post("production.php", action="product", name="hawk", amount="20")
t = one(f"select time from galaxy_productions where login='{A.login_}' and name='hawk'")
check("produção de hawks em fila", t is not None)
advance(A, int(t or 0) * 20 + 5)
check("20 hawks produzidos", int(col(A.login_, "hawk") or 0) >= 20, col(A.login_, "hawk"))

print("== academia (colonos -> soldados)")
s0 = int(col(A.login_, "soldiers"))
A.post("academy.php", action="academy", amount="50")
advance(A, 3)
check("soldados treinados", int(col(A.login_, "soldiers")) > s0, f"{s0} -> {col(A.login_, 'soldiers')}")

print("== mensagens")
A.post("messages.php", action="send", to=B.login_, subject="Olá", message="Mensagem de teste")
check("mensagem entregue", one(f"select count(*) from galaxy_messages where `from`='{A.login_}' and `to`='{B.login_}'") == "1")
check("destinatário vê a mensagem", "Olá" in text(B.get("messages.php")))

print("== clã")
# fundar um clã exige estar num Clan Hall, nível > 10 e 1M de créditos x parâmetro do Clan Hall
sql(DB, f"update galaxy_users set planet='{CLANHALL}', level=11, credits=credits+5000000 where login='{A.login_}'")
A.post("foundclan.php", action="foundclan", name=f"Cla{tag}", description="teste", tax="5", www="")
sql(DB, f"update galaxy_users set planet='{START}' where login='{A.login_}'")
check("clã fundado", one(f"select owner from galaxy_groups where name='Cla{tag}'") == A.login_)
check("fundador pertence ao clã", usr(A.login_, "clan") == f"Cla{tag}", usr(A.login_, "clan"))

print("== viagem")
B.get("control.php", action="travel", destination=BANK)
check("viagem iniciada", usr(B.login_, "destination") == BANK, usr(B.login_, "destination"))
eta = int(usr(B.login_, "time") or 0) - now()
check("duração da viagem razoável (< 1 dia de jogo)", 0 < eta < 288, eta)
advance(B, max(eta, 0) + 2)
check(f"chegou a {BANK}", usr(B.login_, "planet") == BANK, usr(B.login_, "planet"))

print("== banco")
c0, b0 = float(usr(B.login_, "credits")), float(usr(B.login_, "bank"))
forms = [f for f in parse(B.get("bank.php")).forms if f["fields"].get("action")]
check("formulário do banco disponível", bool(forms))
if forms:
    d = dict(forms[0]["fields"])
    for k in d:
        if k not in ("action", "confirm"): d[k] = "1000"; break
    B.post(forms[0]["action"] or "bank.php", **d)
    check("operação no banco", (float(usr(B.login_, "credits")), float(usr(B.login_, "bank"))) != (c0, b0))

print("== ataque")
sql(DB, f"update galaxy_users set level=8, score=1000 where login in ('{A.login_}','{B.login_}')")
colonists0 = int(col(B.login_, "colonists"))
h = A.post("attack.php", action="prepare", name=col(B.login_, "name"))
forms = [f for f in parse(h).forms if f["fields"].get("action") == "attack"]
check("formulário de ataque disponível", bool(forms), text(h)[text(h).find("rror"):][:200])
if forms:
    d = dict(forms[0]["fields"]); d.update(hawk="20", soldiers="10", strategy="1")
    A.post("attack.php", **d)
    end = one(f"select begin+time from galaxy_attacks where login='{A.login_}'")
    check("ataque lançado", end is not None)
    if end:
        check("duração do ataque razoável (< 1 dia de jogo)", int(end) - now() < 288, int(end) - now())
        advance(A, int(end) - now() + 3); B.get("control.php"); advance(A, 5)
        check("ataque concluído e removido", one(f"select count(*) from galaxy_attacks where login='{A.login_}'") == "0")
        check("atacante recebe relatório", one(f"select count(*) from galaxy_messages where `to`='{A.login_}' and subject like 'Attack report%'") != "0")
        check("defensor recebe relatório", one(f"select count(*) from galaxy_messages where `to`='{B.login_}' and subject like 'Attack report%'") != "0")
        check("defensor sofre perdas", int(col(B.login_, "colonists")) < colonists0, f"{colonists0} -> {col(B.login_, 'colonists')}")

print("== páginas principais")
for p in (A, B):
    for page in ("control.php", "colony.php", "structures.php", "units.php", "research.php", "production.php", "equipment.php",
                 "galaxy.php", "highscores.php", "whois.php?name=" + A.login_, "clan.php", "messages.php"):
        code, _ = p.req(page); check(f"{p.login_} {page}", code == 200, code)

errors = php_errors(T0, SERVICE)
check("sem erros fatais PHP no log", not errors, "; ".join(errors))
print(f"\n{UNI}: {ok} OK, {fail} falhas")
sys.exit(1 if fail else 0)
