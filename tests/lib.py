"""Helpers dos testes: jogar por HTTP e ler o estado na base de dados do stack Docker."""
import os, re, subprocess, urllib.request, urllib.parse, urllib.error, http.cookiejar
from html.parser import HTMLParser

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
COMPOSE = ["docker", "compose", "-f", os.path.join(ROOT, "docker-compose.yml")]
DB_USER = os.environ.get("MYSQL_USER", "galaxy")
DB_PASSWORD = os.environ.get("MYSQL_PASSWORD", "galaxy")


class Forms(HTMLParser):
    """Recolhe formulários (campos e opções) e links de uma página."""

    def __init__(self):
        super().__init__()
        self.forms, self.links, self.cur, self.sel = [], [], None, None

    def handle_starttag(self, tag, a):
        a = dict(a)
        if tag == "a" and a.get("href"): self.links.append(a["href"])
        if tag == "form":
            self.cur = {"action": a.get("action", ""), "method": a.get("method", "get").lower(), "fields": {}, "types": {}, "selects": {}}
            self.forms.append(self.cur)
        if not self.cur: return
        n = a.get("name")
        if tag == "input" and n:
            t = a.get("type", "text").lower()
            if t != "submit" or n not in self.cur["fields"]:
                self.cur["fields"].setdefault(n, a.get("value", ""))
                self.cur["types"][n] = t
        if tag == "textarea" and n: self.cur["fields"][n] = ""; self.cur["types"][n] = "textarea"
        if tag == "select" and n: self.sel = n; self.cur["selects"][n] = []
        if tag == "option" and self.sel: self.cur["selects"][self.sel].append(a.get("value", ""))

    def handle_endtag(self, tag):
        if tag == "select": self.sel = None
        if tag == "form": self.cur = None


def parse(html):
    p = Forms(); p.feed(html); return p


def text(html):
    return re.sub(r"\s+", " ", re.sub(r"<[^>]+>", " ", html))


class Player:
    """Um cliente HTTP com cookies, que joga como um utilizador."""

    def __init__(self, base, login="", password=""):
        self.base, self.login_, self.pw, self.logged = base.rstrip("/"), login, password, False
        self.jar = http.cookiejar.CookieJar()
        self.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def req(self, path, data=None):
        url = path if path.startswith("http") else self.base + "/" + path.lstrip("/")
        body = urllib.parse.urlencode(data).encode() if data is not None else None
        try:
            r = self.op.open(url, body, timeout=60)
            return r.status, r.read().decode("utf-8", "replace")
        except urllib.error.HTTPError as e:
            return e.code, e.read().decode("utf-8", "replace")
        except Exception as e:
            return 0, str(e)

    def get(self, page, **query):
        code, html = self.req(page + ("?" + urllib.parse.urlencode(query) if query else ""))
        assert code == 200, (page, code)
        return html

    def post(self, page, **data):
        code, html = self.req(page, data)
        assert code == 200, (page, code)
        return html

    def register(self, email):
        f = [f for f in parse(self.get("register.php")).forms if "login" in f["fields"]][0]
        d = dict(f["fields"])
        for k in d:
            if "pass" in k or k == "reenter": d[k] = self.pw
            if "mail" in k: d[k] = email
        d.update(login=self.login_, agree="on", language="en")
        return self.post(f["action"] or "register.php", **d)

    def login(self):
        f = [f for f in parse(self.get("login.php")).forms if "login" in f["fields"]][0]
        d = dict(f["fields"]); d.update(login=self.login_, password=self.pw)
        html = self.post(f["action"], **d)
        assert "logout" in html.lower(), f"login de {self.login_} falhou"
        self.logged = True
        return html


def sql(db, query):
    """Corre uma query no MySQL do stack e devolve as linhas como listas."""
    r = subprocess.run(COMPOSE + ["exec", "-T", "db", "mysql", "--default-character-set=utf8mb4", f"-u{DB_USER}", f"-p{DB_PASSWORD}", "-N", "-B", db, "-e", query],
                       capture_output=True, text=True)
    return [l.split("\t") for l in r.stdout.strip().split("\n") if l]


def php_errors(since, service, levels=("Fatal error", "Parse error")):
    """Mensagens de erro PHP no log do container desde `since` (ISO 8601), agrupadas e contadas."""
    r = subprocess.run(COMPOSE + ["logs", service, "--since", since, "--no-log-prefix"], capture_output=True, text=True)
    found = {}
    for m in re.finditer(r"PHP (%s):\s+(.*?)(?:\\n|$)" % "|".join(map(re.escape, levels)), r.stdout + r.stderr, re.M):
        msg = re.sub(r" in /var/www/html/([^ ]+) on line (\d+)", r"  @ \1:\2", m.group(2))
        msg = re.sub(r" in /var/www/html/([^:]+):(\d+)", r"  @ \1:\2", msg)
        key = f"{m.group(1)}: {msg.split('Stack trace')[0].strip()}"
        found[key] = found.get(key, 0) + 1
    return found
