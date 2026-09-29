# Galaxy Forces

Jogo de estratégia espacial multijogador para o browser, no estilo de OGame e Travian. Cada jogador tem um
herói que viaja entre planetas e colónias que recolhem recursos, constroem, investigam e atacam outros
jogadores. O tempo passa em ciclos, mesmo com o jogador desligado.

Esta versão parte do Galaxy Forces 0.5.3 (2010) e foi atualizada para **PHP 8.5** e **MySQL 8.4**. Corre em
Docker com dois universos independentes e está traduzida para **português de Portugal**, além de inglês e
polaco.

## Início rápido

É preciso ter o Docker com o Compose v2 (`docker compose`).

```bash
cp .env.example .env
docker compose up -d --build
```

No primeiro arranque, o MySQL cria os universos: esquema, mapa, objetos e utilizador administrador. Demora
cerca de um minuto. Depois disso:

| Universo | Endereço | Ciclo |
|---|---|---|
| Milky Way | http://milkyway.localhost:8088 (também http://localhost:8088) | 5 minutos |
| Andromeda | http://andromeda.localhost:8088 | 1 minuto |

Entre com `admin` / `admin` (os valores de `GALAXY_ADMIN_LOGIN` e `GALAXY_ADMIN_PASSWORD`). Cada universo tem
os seus próprios jogadores.

Os endereços `*.localhost` apontam sozinhos para o próprio computador, sem mexer no `/etc/hosts`. Os
universos precisam de nomes diferentes porque os cookies de sessão não distinguem portas: em
`localhost:8088` e `localhost:8089`, entrar num universo faria sair do outro.

> **Antes de expor o jogo fora do seu computador**, mude todas as palavras-passe no `.env`. A porta
> `GALAXY_PORT` fica aberta em todas as interfaces de rede.

## Configuração

Tudo se configura no ficheiro `.env`. Depois de o alterar, aplique as mudanças com `docker compose up -d`.

| Variável | Omissão | Para que serve |
|---|---|---|
| `GALAXY_PORT` | `8088` | Porta onde o jogo fica disponível |
| `MYSQL_ROOT_PASSWORD` | `root` | Palavra-passe do root do MySQL |
| `MYSQL_USER` / `MYSQL_PASSWORD` | `galaxy` / `galaxy` | Utilizador do MySQL usado pelo jogo |
| `GALAXY_ADMIN_LOGIN` / `GALAXY_ADMIN_PASSWORD` | `admin` / `admin` | Administrador criado em cada universo |
| `GALAXY_ADMIN_EMAIL` | `admin@localhost` | E-mail do administrador |
| `GALAXY_ADMIN_CONFIRM` | vazio | Palavra-passe pedida para apagar contas antigas ou clãs no painel de administração. Vazio desativa essas ações |
| `MILKYWAY_TICK` / `ANDROMEDA_TICK` | `300` / `60` | Segundos por ciclo de jogo em cada universo |
| `GALAXY_DEFAULT_LANGUAGE` | `pt` | Idioma para quem o browser não indica um idioma disponível (`en`, `pl`, `pt`) |
| `GALAXY_STYLE` | `nova` | Tema visual: `nova` (moderno, também em telemóvel) ou `galaxy` (o original de 2010) |

As variáveis do administrador e do MySQL só têm efeito quando os universos são criados, ou seja, no primeiro
arranque. Mudá-las depois não altera contas que já existem.

Não mude a duração do ciclo num universo que já está a ser jogado. A data estelar é calculada a partir dela,
e alterá-la desloca todas as datas de construções, investigações, viagens e ataques.

## Como está montado

```
                     ┌─ web-milkyway  (PHP 8.5 + Apache) ─┐
browser ── proxy ────┤                                    ├── db (MySQL 8.4)
          (nginx)    └─ web-andromeda (PHP 8.5 + Apache) ─┘    galaxy_milkyway
                                                                galaxy_andromeda
```

- **proxy**: nginx que encaminha cada nome (`milkyway.localhost`, `andromeda.localhost`) para o universo
  certo. Configuração em [docker/nginx/universes.conf](docker/nginx/universes.conf).
- **web-***: um container por universo. Todos usam o mesmo código (a pasta do projeto está montada, por isso
  as alterações ao código aparecem sem reconstruir a imagem), cada um com a sua base de dados, título, ciclo e
  pasta de logs.
- **db**: um MySQL com uma base de dados por universo, em `utf8mb4`. No primeiro arranque,
  [docker/mysql/00-universes.sh](docker/mysql/00-universes.sh) cria cada universo listado em
  `GALAXY_UNIVERSES`.

O aspeto vem do tema em `style/<tema>/`: `header.php` e `footer.php` desenham a estrutura da página
(barra de recursos, menu, colunas), `style.php` as caixas e `style.css` o resto. As páginas do jogo não
dependem do tema.

Os dados ficam em volumes do Docker: `db-data-mysql8` (base de dados), `log-milkyway` e `log-andromeda`.

## Centro de Operações

Ferramenta interna de manutenção, gestão e monitorização, só para administradores (grupo `wheel`). Está em
`ops.php` e aparece no menu como **Operações**. Cada universo tem a sua, com os dados dessa base de dados.

| Separador | O que mostra ou permite |
|---|---|
| Painel | Jogadores (total, ativos, online, novos), colónias, clãs, filas em curso, logins falhados, colónias atrasadas no motor, registos e logins por dia, atividade recente, melhores jogadores |
| Jogadores | Pesquisa por login, e-mail ou IP; por jogador: dados, colónia, histórico e ações (mudar grupo, dar ou tirar créditos e recursos, bloquear, banir, mover o herói, repor a palavra-passe, terminar a sessão, enviar mensagem, apagar a conta) |
| Colónias | Todas as colónias, com recursos, dano e ciclos em atraso |
| Atividade | O registo de auditoria (logins, registos, colónias, ataques, clãs, ações de administração), com filtros |
| Filas | Construções, investigações, produções, expedições e ataques em curso, com o tempo restante |
| Economia | Totais de créditos e recursos no universo, jogadores mais ricos, preços dos mercados |
| Manutenção | Modo de manutenção só deste universo, mensagem para o chat, notícias, processar colónias atrasadas, limpar dados antigos, otimizar tabelas |
| Sistema | Versões de PHP e MySQL, configuração, tamanho das tabelas, espaço em disco e o fim dos ficheiros de registo |

As ações só funcionam por POST, com um token ligado à sessão do administrador, e ficam todas no registo de
auditoria (tabela `galaxy_audit`, criada sozinha nas bases que ainda não a têm). Apagar uma conta pede a
palavra-passe de confirmação `GALAXY_ADMIN_CONFIRM`. Com o modo de manutenção ativo, os administradores
continuam a poder entrar e jogar.

## Comandos úteis

Ver os logs, incluindo os erros PHP de um universo:

```bash
docker compose logs -f web-milkyway
```

Parar sem perder dados:

```bash
docker compose down
```

Apagar tudo e começar de novo (**apaga todos os jogadores e colónias**):

```bash
docker compose down -v && docker compose up -d
```

Abrir uma consola MySQL num universo:

```bash
docker compose exec db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" galaxy_milkyway'
```

### Cópias de segurança

Criar uma cópia de todos os universos:

```bash
docker compose exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --databases galaxy_milkyway galaxy_andromeda' > backup.sql
```

Repor a cópia (substitui as bases de dados que ela contém):

```bash
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < backup.sql
```

## Acrescentar um universo

Um universo é definido por `nome:mapa:galáxias:planeta_inicial`. Os mapas e galáxias estão em `sql/`
(`world.sql` e `universe.sql` para a Milky Way, `world-andromeda.sql` e `universe-andromeda.sql` para a
Andromeda). Por exemplo, para um universo `orion` com o mapa da Andromeda:

1. Em `docker-compose.yml`, acrescente a linha `orion:world-andromeda.sql:universe-andromeda.sql:horus` a
   `GALAXY_UNIVERSES` e um serviço `web-orion`, copiando o `web-andromeda` e mudando `DB_NAME` para
   `galaxy_orion`, o título, o ciclo e o volume de logs (declare também o volume `log-orion`).
2. Em [docker/nginx/universes.conf](docker/nginx/universes.conf), acrescente um bloco `server` para
   `orion.localhost` com `set $upstream web-orion;`.
3. Crie a base de dados do universo novo sem tocar nos existentes:

   ```bash
   docker compose exec db sh -c 'GALAXY_UNIVERSES="orion:world-andromeda.sql:universe-andromeda.sql:horus" /docker-entrypoint-initdb.d/00-universes.sh'
   ```

4. Arranque os serviços novos e o proxy:

   ```bash
   docker compose up -d
   ```

O universo fica em http://orion.localhost:8088. Numa instalação nova, basta o passo 1: o universo é criado com
os outros no primeiro arranque.

## Visão geral

Depois de entrar, o jogo abre na **Visão Geral** (`overview.php`), a página de entrada ao estilo do OGame:
a colónia num relance, a produção de recursos por hora com o tempo até encher os armazéns, o que está em
curso (construção, investigação, produção) e os eventos (viagem do herói, expedições e ataques a chegar ou a
sair). Não muda nenhuma regra do jogo: só mostra o estado que o motor já calcula.

## Idiomas

O jogo escolhe o idioma pelo browser: português (`pt`), inglês (`en`) ou polaco (`pl`). Para quem o browser
não pede nenhum destes, usa `GALAXY_DEFAULT_LANGUAGE`. Cada jogador pode mudar de idioma no perfil.

As traduções estão em `locale/<idioma>/`. Um texto que falte numa tradução aparece em inglês. Para acrescentar
um idioma, crie `locale/<código>/` com os mesmos ficheiros de `locale/en/`. O novo idioma aparece sozinho nas
listas do registo e do perfil. As dicas do dia estão na tabela `galaxy_tips`, em `sql/install.sql`.

## Testes

Testes de ponta a ponta contra o stack a correr, só com Python 3:

```bash
python3 tests/scenario.py
```

```bash
python3 tests/smoke.py
```

O `scenario.py` joga um jogo completo com dois jogadores (colónia, construção, investigação, produção, clã,
viagem, banco e um ataque) e verifica cada passo na base de dados. O `smoke.py` visita todas as páginas e
formulários à procura de erros PHP. Os dois criam jogadores de teste. Mais pormenores em
[tests/README.md](tests/README.md).

## Instalação sem Docker

É preciso PHP 8.x com a extensão `mysqli`, um servidor web (por exemplo Apache com `AllowOverride All`) e
MySQL 8.x.

1. Crie uma base de dados em `utf8mb4` e um utilizador com acesso a ela:

   ```sql
   CREATE DATABASE galaxy CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

2. Esvazie o `include/config.php` (o instalador só corre quando este ficheiro está vazio) e dê permissões de
   escrita ao servidor web sobre `include/config.php` e `log/`.
3. Abra `install.php` no browser e preencha o formulário. O instalador cria as tabelas, o mapa, os objetos e
   o administrador, e escreve o `include/config.php`.
4. Apague o `install.php` no fim e proteja o `include/config.php`.

O PHP deve ter `output_buffering` ativo (por exemplo `32768`). Num servidor dedicado, `zlib.output_compression`
também ajuda. Veja [docker/php/galaxy.ini](docker/php/galaxy.ini) para a configuração usada no Docker.

## Palavras-passe

As palavras-passe são guardadas com `password_hash` (bcrypt). As contas antigas, guardadas em MD5, continuam
a funcionar: no primeiro login, a palavra-passe é convertida para bcrypt automaticamente.

Numa base de dados que já existia antes desta mudança, alargue a coluna uma vez (os hashes bcrypt têm 60
caracteres, não cabem em `varchar(32)`):

```bash
docker compose exec db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "ALTER TABLE galaxy_milkyway.galaxy_users MODIFY password varchar(255) NOT NULL DEFAULT ''; ALTER TABLE galaxy_andromeda.galaxy_users MODIFY password varchar(255) NOT NULL DEFAULT '';"'
```

Instalações novas já têm a coluna com o tamanho certo.

## Problemas conhecidos

- O `calendar.php` é um resto do phpMyAdmin, depende de bibliotecas que não existem no projeto e nunca
  funcionou.
- Abrir a página inicial (`default.php`) ou a de manutenção termina a sessão. Já era assim no original.
- A página de contacto mostra o e-mail do autor original.

## Origem e licença

Galaxy Forces foi criado por Filip Golewski (zoltarx) e pela equipa listada em [CREW.txt](CREW.txt). O
histórico de versões está em [CHANGES.txt](CHANGES.txt).

É software livre, distribuído nos termos da GNU General Public License, versão 2 ou (à sua escolha) qualquer
versão posterior. O texto completo está na página de licença do jogo (`licence.php`).
