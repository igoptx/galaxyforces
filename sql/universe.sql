-- Galáxias do universo principal {universe.sql}
-- A tabela galaxy_universe nunca teve dados no projeto original; o galaxy.php
-- precisa dela para mostrar o mapa do universo e calcular distâncias entre galáxias.
-- A distância entre galáxias é multiplicada por 1000 (galaxydistance()), por isso
-- coordenadas pequenas: 1 unidade ~ 700 ciclos de viagem (~2,5 dias a 5 min/ciclo).

INSERT IGNORE INTO `galaxy_universe` (`name`, `x`, `y`, `z`, `type`, `discovered`, `by`, `age`) VALUES
('milky_way',   0,  0,  0, 'galaxy',  '', '', ''),
('onion',       1, -1,  0, 'galaxy',  '', '', ''),
('tron',       -1,  1,  0, 'galaxy',  '', '', ''),
('wolf',        0,  2, -1, 'galaxy',  '', '', ''),
('maya',       -2, -1,  1, 'galaxy',  '', '', ''),
('plexi',       2,  1,  1, 'galaxy',  '', '', ''),
('underverse',  0, -2, -2, 'anomaly', '', '', '');
