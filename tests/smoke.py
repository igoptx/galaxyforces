#!/usr/bin/env python3
"""Teste de fumo: visita todas as páginas e submete os seus formulários, procurando erros PHP.

Corre como visitante anónimo, jogador novo (sem e com colónia, antes e depois de
ciclos do motor) e admin. No fim agrupa os erros fatais e warnings do log do container.

    python3 tests/smoke.py                   # milkyway
    python3 tests/smoke.py andromeda --admin-password admin

Submete formulários com valores por omissão, por isso deixa dados de teste na base de
dados (jogadores, mensagens, anúncios...). Evita ações destrutivas (apagar, banir, logout).
"""
import argparse, datetime, glob, os, random, re, sys, time, urllib.parse
from lib import ROOT, Player, parse, php_errors, sql

# páginas que nunca funcionaram no projeto original
KNOWN_BROKEN = set()   # calendar.php (resto do phpMyAdmin) foi removido
SKIP = {"install.php", "index.php", "hash.php"}          # install reescreve o config.php
INTERNAL = {"default.php", "maintenance.php"}           # não usam a base de dados e terminam a sessão
DANGEROUS = re.compile(r"logout|delete|remove|ban|lock|drop|abandon|reset|kill|truncate|jail", re.I)
START = {"milkyway": "mulahay", "andromeda": "horus"}

ap = argparse.ArgumentParser(description=__doc__.split("\n")[0])
ap.add_argument("universe", nargs="?", default="milkyway", choices=START)
ap.add_argument("--base", help="URL do universo (por omissão http://<universo>.localhost:8088)")
ap.add_argument("--admin-login", default=os.environ.get("GALAXY_ADMIN_LOGIN", "admin"))
ap.add_argument("--admin-password", default=os.environ.get("GALAXY_ADMIN_PASSWORD", "admin"))
args = ap.parse_args()
UNI, BASE, DB = args.universe, args.base or f"http://{args.universe}.localhost:8088", f"galaxy_{args.universe}"
PAGES = sorted(os.path.basename(p) for p in glob.glob(os.path.join(ROOT, "*.php")))
T0 = datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
status = {}


def visit(c, path, data=None):
    code, html = c.req(path, data)
    status.setdefault(path.split("?")[0].lstrip("/"), set()).add(code)
    return html


def submit_forms(c, page, html):
    for f in parse(html).forms:
        if DANGEROUS.search(str(f)): continue
        # com sessão, não submeter login/registo nem formulários com password (trocavam de utilizador)
        if c.logged and (page in ("register.php", "login.php", "lostpassword.php") or "password" in f["types"].values()): continue
        fields = {k: (v or ("1" if f["types"].get(k) in ("text", "number") else "teste" if f["types"].get(k) == "textarea" else v))
                  for k, v in f["fields"].items()}
        for k, opts in f["selects"].items():
            fields.setdefault(k, opts[0] if opts else "")
        act = f["action"] or page
        act = act.split("//", 1)[-1].split("/", 1)[-1] if act.startswith("http") else act.lstrip("/")
        if f["method"] == "post": visit(c, act, fields)
        else: visit(c, act.split("?")[0] + "?" + urllib.parse.urlencode(fields))


def crawl(c, label):
    print(f"== {label}")
    for p in PAGES:
        if p in SKIP or (c.logged and p in INTERNAL): continue
        html = visit(c, p)
        if p == "ops.php":   # Centro de Operações: só visitar (os formulários mexem no universo inteiro)
            for v in ("dashboard", "players", "colonies", "activity", "queues", "economy", "maintenance", "system"):
                visit(c, f"ops.php?view={v}")
            continue
        submit_forms(c, p, html)
        links = [l for l in parse(html).links if ".php?" in l and not l.startswith(("http", "javascript")) and not DANGEROUS.search(l)]
        for l in links[:25]: visit(c, l.lstrip("/"))


crawl(Player(BASE), "visitante anónimo")

tag = random.randint(1000, 9999)
new = Player(BASE, f"smoke{tag}", "segredo1")
new.register(new.login_ + "@example.test"); new.login()
crawl(new, f"jogador novo ({new.login_})")

visit(new, "colony.php", {"action": "create", "name": f"col{tag}", "planet": START[UNI]})
sql(DB, f"update galaxy_users set credits=10000000, level=10 where login='{new.login_}'")
sql(DB, f"update galaxy_colonies set metal=1000000, energy=1000000, silicon=100000, food=100000, colonists=500, scientists=100, soldiers=100, "
        f"crystals=1000, uran=1000, factory=5, laboratory=5, flats=10, barracks=5, academy=2, bunker=2, foodplanting=5, windgenerator=5, "
        f"spacedepot=2, militarytechnology=2 where owner='{new.login_}'")
crawl(new, "jogador com colónia")
for k in (5, 10, 10):   # o motor processa no máximo 10 ciclos por pedido
    sql(DB, f"update galaxy_colonies set thicks=thicks-{k} where owner='{new.login_}'")
    sql(DB, f"update galaxy_users set thicks=thicks-{k} where login='{new.login_}'")
    for p in ("control.php", "colony.php", "structures.php", "units.php", "research.php", "production.php", "build.php", "galaxy.php"):
        visit(new, p)
crawl(new, "jogador após ciclos do motor")

admin = Player(BASE, args.admin_login, args.admin_password)
admin.login()
crawl(admin, "admin")

time.sleep(1)
fatal = php_errors(T0, f"web-{UNI}")
warnings = php_errors(T0, f"web-{UNI}", levels=("Warning",))
bad_pages = sorted(p for p, codes in status.items() if any(c >= 500 or c == 0 for c in codes))
unexpected = [p for p in bad_pages if p not in KNOWN_BROKEN]
unexpected_fatal = {m: n for m, n in fatal.items() if not any(f"@ {k}" in m for k in KNOWN_BROKEN)}

print(f"\n{len(status)} URLs visitados, {sum(warnings.values())} warnings PHP")
print("páginas com erro 5xx:", ", ".join(bad_pages) or "nenhuma", f"(conhecidas: {', '.join(sorted(KNOWN_BROKEN))})")
for m, n in sorted(fatal.items(), key=lambda x: -x[1]): print(f"  {n:4} {m}")
if unexpected or unexpected_fatal:
    print("FALHA: erros fatais inesperados"); sys.exit(1)
print("OK: nenhum erro fatal inesperado")
