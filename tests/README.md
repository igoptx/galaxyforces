# Testes

Testes de ponta a ponta contra o stack Docker (`docker compose up -d`). Só usam a
biblioteca padrão do Python 3; falam com o jogo por HTTP e leem o estado com
`docker compose exec db mysql`.

```bash
python3 tests/scenario.py            # cenário de jogo com verificações (milkyway)
python3 tests/scenario.py andromeda
python3 tests/smoke.py               # visita todas as páginas e formulários à procura de erros PHP
python3 tests/rules.py               # regras de jogo que já estiveram partidas
```

- **scenario.py**: dois jogadores fazem um jogo completo (colónia, construção,
  recursos por ciclo, pesquisa, produção, academia, mensagens, clã, viagem, banco e
  um ataque de um ao outro). Cada passo é verificado na base de dados. O relógio do
  jogo é avançado recuando as colunas de tempo, em blocos de 10 ciclos (o máximo que
  o motor processa por pedido). Imprime também os recursos da colónia após 5 ciclos,
  útil para comparar resultados entre versões de PHP.
- **smoke.py**: percorre todas as páginas como visitante, jogador novo (sem e com
  colónia, antes e depois de ciclos) e admin, submete os formulários com valores por
  omissão e agrupa os erros PHP do log do container. Falha com erros fatais
  inesperados; `calendar.php` está listado como conhecido (nunca funcionou).

Os dois scripts criam jogadores e dados de teste. Para voltar a um estado limpo:
`docker compose down -v && docker compose up -d`.
- **rules.py**: verifica, com jogo normal, regras que estiveram partidas: uma colónia por
  jogador, a academia cobra os soldados, quantidades fracionárias contam como inteiras, as
  percentagens de gestão têm mínimo e abandonar uma colónia custa experiência.
