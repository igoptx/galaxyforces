-- Universo Andromeda {universe-andromeda.sql}
-- Complementa o world-andromeda.sql (que só traz planetas) com a galáxia,
-- locais e mercado, para o universo ser jogável. Planeta inicial: horus.

INSERT IGNORE INTO `galaxy_universe` (`name`, `x`, `y`, `z`, `type`, `discovered`, `by`, `age`) VALUES
('andromeda', 0, 0, 0, 'galaxy', '', '', '');

INSERT IGNORE INTO `galaxy_places` (`position`, `type`, `parameters`, `extra`, `level`, `reputation`) VALUES
('horus', 'academy', '1000', '', 0, 0),
('horus', 'mercenary', '1', '', 0, 0),
('horus', 'tracker', '500', '', 0, 0),
('horus', 'healer', '1', '', 0, 0),
('horus', 'market', '', '', 0, 0),
('horus', 'itemshop', 'knife,lightarmor,belt,xtd,pins,lance,helmet,paralyzer', '', 0, 0),
('horus', 'teleport', 'lira', '10000', 0, 0),
('gaja', 'arena', '', '', 0, 0),
('gaja', 'bank', '500000', '', 0, 0),
('gaja', 'mines', '1', '', 0, 0),
('lira', 'arena', '', '', 0, 0),
('lira', 'clanhall', '2', '', 0, 0),
('lira', 'gambler', '30', '', 0, 0),
('lira', 'mines', '2', '', 0, 0),
('lira', 'teleport', 'horus', '10000', 0, 0);

INSERT INTO `galaxy_markets` ( `position` , `level` , `reputation` , `energybuyaverage` , `energybuy` , `energysellaverage` , `energysell` , `siliconbuyaverage` , `siliconbuy` , `siliconsellaverage` , `siliconsell` , `metalbuyaverage` , `metalbuy` , `metalsellaverage` , `metalsell` , `uranbuyaverage` , `uranbuy` , `uransellaverage` , `uransell` , `plutoniumbuyaverage` , `plutoniumbuy` , `plutoniumsellaverage` , `plutoniumsell` , `deuteriumbuyaverage` , `deuteriumbuy` , `deuteriumsellaverage` , `deuteriumsell` , `foodbuyaverage` , `foodbuy` , `foodsellaverage` , `foodsell` , `crystalsbuyaverage` , `crystalsbuy` , `crystalssellaverage` , `crystalssell` )
VALUES (
'horus', '0', '-3', '100', '0', '0.05', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'
);
