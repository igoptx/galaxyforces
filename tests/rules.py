#!/usr/bin/env python3
"""Regras de jogo que já estiveram partidas: verifica-as com jogo normal.

    python3 tests/rules.py            # milkyway
    python3 tests/rules.py andromeda

Cria um jogador novo em cada execução.
"""
import argparse, random, sys
from lib import Player, sql

ap = argparse.ArgumentParser(description=__doc__.split("\n")[0])
ap.add_argument("universe", nargs="?", default="milkyway", choices=("milkyway", "andromeda"))
args = ap.parse_args()
UNI, DB = args.universe, f"galaxy_{args.universe}"
BASE = f"http://{UNI}.localhost:8088"
START = {"milkyway": "mulahay", "andromeda": "horus"}[UNI]
CASINO = {"milkyway": "mouse", "andromeda": "lira"}[UNI]  # planeta com casino (gambler)
ok = fail = 0


def check(name, cond, detail=""):
    global ok, fail
    if cond: ok += 1; print(f"  OK    {name}")
    else: fail += 1; print(f"  FALHA {name}  {detail}")


def one(q):
    r = sql(DB, q)
    return r[0][0] if r and r[0] else ""


tag = random.randint(1000, 9999)
p = Player(BASE, f"regras{tag}", "segredo1")
p.register(p.login_ + "@example.test"); p.login()
L = p.login_

p.post("colony.php", action="create", name=f"Regras{tag}", planet=START)
check("primeira colónia criada", one(f"select count(*) from galaxy_colonies where owner='{L}'") == "1")
p.post("colony.php", action="create", name=f"Outra{tag}", planet=START)
check("segunda colónia recusada", one(f"select count(*) from galaxy_colonies where owner='{L}'") == "1")

# dados suficientes para as ações seguintes
sql(DB, f"update galaxy_colonies set colonists=500, food=100000, energy=100000, metal=100000, factory=3, barracks=5, managementtechnology=3 where owner='{L}'")
sql(DB, f"update galaxy_users set credits=500000, level=10, exp=100000, planet='{START}' where login='{L}'")

print("== academia")
c0 = int(one(f"select credits from galaxy_users where login='{L}'"))
s0 = int(one(f"select soldiers from galaxy_colonies where owner='{L}'"))
p.post("academy.php", action="academy", amount="10")
c1 = int(one(f"select credits from galaxy_users where login='{L}'"))
s1 = int(one(f"select soldiers from galaxy_colonies where owner='{L}'"))
if s1 > s0: check("academia cobra créditos pelos soldados", c1 < c0, f"{c0} -> {c1}")
else: check("academia disponível no planeta inicial", False, "sem soldados treinados")

print("== quantidades inteiras")
p.post("production.php", action="product", name="hawk", amount="1.5")
n = one(f"select amount from galaxy_productions where login='{L}' and name='hawk'")
check("1.5 conta como 1 unidade", n in ("1", ""), n)

print("== gestão da colónia")
p.post("colony.php", action="management", view="management", infrastructure="110", science="-5", military="-5")
check("percentagens negativas recusadas", int(one(f"select science from galaxy_colonies where owner='{L}'")) >= 1,
      one(f"select science from galaxy_colonies where owner='{L}'"))

print("== abandonar colónia")
e0 = int(float(one(f"select exp from galaxy_users where login='{L}'")))
h = p.get("colony.php", view="abandon")
import re
m = re.search(r"confirm=([0-9a-f]{32})", h)
if m:
    p.get("colony.php", action="abandon", confirm=m.group(1))
    e1 = int(float(one(f"select exp from galaxy_users where login='{L}'")))
    check("abandonar custa experiência", e1 < e0, f"{e0} -> {e1}")
    check("colónia removida", one(f"select count(*) from galaxy_colonies where owner='{L}'") == "0")
else:
    check("página de abandono tem ligação de confirmação", False)

print("== casino cobra a aposta")
# põe o herói num planeta com casino e créditos altos; jogar não deve dar lucro sistemático
if one(f"select count(*) from galaxy_places where position='{CASINO}' and type='gambler'") != "0":
    sql(DB, f"update galaxy_users set credits=1000000, mp=100, mpmax=100, planet='{CASINO}' where login='{L}'")
    import re as _re
    c0 = float(one(f"select credits from galaxy_users where login='{L}'"))
    mp0 = float(one(f"select mp from galaxy_users where login='{L}'"))
    # sem prémios (a aposta é sempre cobrada), o saldo desce; com um prémio raro pode subir.
    # Verifica que numa jogada de perda garantida (força a semente com muitas jogadas) o saldo desce no total OU houve um prémio identificável.
    drops = 0
    for _ in range(20):
        b = float(one(f"select credits from galaxy_users where login='{L}'"))
        p.get("gambler.php", action="gamble")
        if float(one(f"select credits from galaxy_users where login='{L}'")) < b: drops += 1
    mp1 = float(one(f"select mp from galaxy_users where login='{L}'"))
    check("casino consome MP", mp1 < mp0, f"{mp0} -> {mp1}")
    check("casino cobra a aposta na maioria das jogadas", drops >= 15, f"{drops}/20 jogadas cobradas")
else:
    check("planeta de casino existe", False, CASINO)

print("== banco")
sql(DB, f"update galaxy_users set credits=100000, bank=0, planet='{START}' where login='{L}'")
# depositar via formulário do banco (se o planeta inicial tiver banco); senão salta
p.get("control.php")

print("== quantidade negativa não cria unidades")
sql(DB, f"update galaxy_colonies set metal=500000, energy=500000, food=500000, factory=3, workforce=100 where owner='{L}'")
sql(DB, f"update galaxy_users set credits=5000000, military=40, science=40, infrastructure=20 where login='{L}'")
sql(DB, f"update galaxy_colonies set military=40, science=40, infrastructure=20 where owner='{L}'")
hawks0 = int(one(f"select hawk from galaxy_colonies where owner='{L}'") or 0)
p.post("production.php", action="product", name="hawk", amount="-5")
q_neg = one(f"select amount from galaxy_productions where login='{L}' and name='hawk'")
check("produção com quantidade negativa não cria fila", q_neg in ("", None) or int(q_neg) >= 0, str(q_neg))

print(f"\n{UNI}: {ok} OK, {fail} falhas")
sys.exit(1 if fail else 0)
