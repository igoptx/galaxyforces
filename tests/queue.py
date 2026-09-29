#!/usr/bin/env python3
"""Fila de construção e investigação (estilo OGame): verifica-a com jogo real.

    python3 tests/queue.py            # milkyway
    python3 tests/queue.py andromeda

Cria um jogador novo em cada execução. Controla o estado por SQL (fixa
`thicks` para evitar o catch-up do motor e os limites de capacidade) para
que a fila seja observável de forma determinística.
"""
import argparse, random, re, sys
from lib import Player, sql

ap = argparse.ArgumentParser(description=__doc__.split("\n")[0])
ap.add_argument("universe", nargs="?", default="milkyway", choices=("milkyway", "andromeda"))
args = ap.parse_args()
UNI, DB = args.universe, f"galaxy_{args.universe}"
BASE = f"http://{UNI}.localhost:8088"
START = {"milkyway": "mulahay", "andromeda": "horus"}[UNI]
ok = fail = 0


def check(name, cond, detail=""):
    global ok, fail
    if cond: ok += 1; print(f"  OK    {name}")
    else: fail += 1; print(f"  FALHA {name}  {detail}")


def one(q):
    r = sql(DB, q)
    return r[0][0] if r and r[0] else ""


tag = random.randint(100000, 999999)
p = Player(BASE, f"fila{tag}", "segredo1")
p.register(p.login_ + "@example.test"); p.login()
L = p.login_


def rid(page="build.php"):
    h = p.get(page)
    m = re.search(r"[?&]rid=([0-9a-f]+)", h)
    return m.group(1) if m else ""


p.post("colony.php", action="create", name=f"Fila{tag}", planet=START, rid=rid("colony.php"))
p.get("build.php")
sd = int(one(f"select thicks from galaxy_colonies where owner='{L}'") or 0)

# só colunas reais (colonistsfree/scientistsfree/workforce são calculadas)
sql(DB, f"update galaxy_colonies set base=3, factory=1, laboratory=3, databank=2, infrastructure=30, science=30, scientists=800, colonists=1000, thicks={sd} where owner='{L}'")
sql(DB, f"update galaxy_users set credits=999999999, level=12, thicks={sd} where login='{L}'")

print("== construção: fila")
# item ativo que nunca termina, para o build seguinte ir para a fila
sql(DB, f"insert into galaxy_buildings (login,name,begin,time,amount,score) values ('{L}','flats',{sd},1000000,1,10)")
sql(DB, f"update galaxy_colonies set thicks={sd} where owner='{L}'")
c0 = int(one(f"select credits from galaxy_users where login='{L}'"))
p.post("build.php", action="build", name="windgenerator", amount="1", rid=rid())
check("build entra na fila com item ativo", one(f"select name from galaxy_buildqueue where login='{L}'") == "windgenerator")
check("build na fila cobra recursos", int(one(f"select credits from galaxy_users where login='{L}'")) < c0)
sql(DB, f"update galaxy_buildqueue set time=1000000 where login='{L}'")

print("== construção: limite da fila (5)")
for _ in range(6):
    sql(DB, f"insert into galaxy_buildqueue (login,name,time,amount,score,credits) values ('{L}','metalsilo',1000000,1,10,0)")
# já lá estão >5; tentar mais um pelo jogo tem de ser recusado
sql(DB, f"delete from galaxy_buildqueue where login='{L}'")
for _ in range(5):
    sql(DB, f"insert into galaxy_buildqueue (login,name,time,amount,score,credits) values ('{L}','metalsilo',1000000,1,10,0)")
r = p.post("build.php", action="build", name="windgenerator", amount="1", rid=rid())
n = int(one(f"select count(*) from galaxy_buildqueue where login='{L}'"))
check("build recusado com fila cheia (5)", n == 5, f"count={n}")

print("== construção: remover devolve recursos")
sql(DB, f"delete from galaxy_buildqueue where login='{L}'")
sql(DB, f"insert into galaxy_buildqueue (login,name,time,amount,score,credits,metal) values ('{L}','metalextractor',1000000,1,10,5000,7000)")
qid = one(f"select id from galaxy_buildqueue where login='{L}' order by id desc limit 1")
c1 = int(one(f"select credits from galaxy_users where login='{L}'"))
p.get("build.php", action="dequeuebuild", id=qid, rid=rid())
check("remover da fila devolve créditos", int(one(f"select credits from galaxy_users where login='{L}'")) == c1 + 5000)
check("remover da fila esvazia a linha", one(f"select count(*) from galaxy_buildqueue where login='{L}'") == "0")

print("== construção: ao terminar arranca o próximo")
sql(DB, f"delete from galaxy_buildings where login='{L}'")
sql(DB, f"delete from galaxy_buildqueue where login='{L}'")
sql(DB, f"insert into galaxy_buildings (login,name,begin,time,amount,score) values ('{L}','flats',{sd-100},10,1,10)")   # end=sd-90
sql(DB, f"insert into galaxy_buildqueue (login,name,time,amount,score) values ('{L}','windgenerator',1000000,1,10)")
sql(DB, f"update galaxy_colonies set thicks={sd-100} where owner='{L}'")
lvl0 = int(one(f"select flats from galaxy_colonies where owner='{L}'") or 0)
p.get("build.php")
check("construção terminada aplica o nível", int(one(f"select flats from galaxy_colonies where owner='{L}'") or 0) == lvl0 + 1)
check("próximo da fila fica ativo", one(f"select name from galaxy_buildings where login='{L}'") == "windgenerator")
check("fila fica vazia depois de arrancar", one(f"select count(*) from galaxy_buildqueue where login='{L}'") == "0")

print("== investigação: ao terminar arranca a próxima")
sql(DB, f"delete from galaxy_researches where login='{L}'")
sql(DB, f"delete from galaxy_researchqueue where login='{L}'")
sql(DB, f"insert into galaxy_researches (login,name,begin,time,score) values ('{L}','managementtechnology',{sd-100},10,10)")
sql(DB, f"insert into galaxy_researchqueue (login,name,time,score) values ('{L}','resourcestechnology',1000000,10)")
sql(DB, f"update galaxy_colonies set thicks={sd-100} where owner='{L}'")
rlvl0 = int(one(f"select managementtechnology from galaxy_colonies where owner='{L}'") or 0)
p.get("research.php")
check("investigação terminada sobe o nível", int(one(f"select managementtechnology from galaxy_colonies where owner='{L}'") or 0) == rlvl0 + 1)
check("próxima investigação fica ativa", one(f"select name from galaxy_researches where login='{L}'") == "resourcestechnology")

# limpeza
for t in ("users", "colonies", "buildings", "buildqueue", "researches", "researchqueue"):
    col = "owner" if t == "colonies" else "login"
    sql(DB, f"delete from galaxy_{t} where {col}='{L}'")

print(f"\n{UNI}: {ok} OK, {fail} falhas")
sys.exit(1 if fail else 0)
