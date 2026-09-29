<?php

global $Config;
global $Sections;
global $Lang;
global $User;

locale('module/bug', $Config['Language']);

if (!isset($Config[$key="module.bug.group"])) $Config[$key]="wheel";

// Só em depuração (GALAXY_DEBUG=1): despeja $Config/$User/$Player/... e pode
// expor segredos. Em produção (Debug desligado) nunca aparece, nem a admins.
if (!empty($Config['Debug']) && in_array($Config[$key],(array)@$User["usergroup"]))
{
	if (isset($Config[$key="module.bug.section"])) $Sections[$Config[$key]][] = "bug";
}
