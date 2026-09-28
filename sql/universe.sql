-- Galáxias do universo principal {universe.sql}
-- A tabela galaxy_universe nunca teve dados no projeto original; o galaxy.php
-- precisa dela para mostrar o mapa do universo e calcular distâncias entre galáxias.

INSERT IGNORE INTO `galaxy_universe` (`name`, `x`, `y`, `z`, `type`, `discovered`, `by`, `age`) VALUES
('milky_way',   0,   0,   0, 'galaxy',  '', '', ''),
('onion',      12,  -4,   3, 'galaxy',  '', '', ''),
('tron',       -9,   7,  -2, 'galaxy',  '', '', ''),
('wolf',        5,  14,  -6, 'galaxy',  '', '', ''),
('maya',      -15,  -8,   4, 'galaxy',  '', '', ''),
('plexi',      20,   9,  11, 'galaxy',  '', '', ''),
('underverse', -3, -21, -17, 'anomaly', '', '', '');
