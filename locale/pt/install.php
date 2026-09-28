<?php

$Lang['ErrorInstall1']='Parece que já tem tudo instalado/atualizado, pelo que, por razões de segurança, o acesso a este ficheiro está restringido!<br />Tenha em atenção que este ficheiro (<b>install.php</b>) deve ser apagado após uma instalação bem-sucedida!';
//$Lang['ErrorInstall1']='Parece que tudo já está instalado/atualizado, pelo que, por razões de segurança, o acesso a este ficheiro foi bloqueado!<br />Tenha em atenção que este ficheiro (<b>install.php</b>) deve ser apagado após uma instalação correta!';
$Lang['Install1']='Instalação do Galaxy Forces';
//$Lang['Install1']='Instalação do Galaxy Forces';
$Lang['Warning']='Aviso';
//$Lang['Warning']='Aviso';

$Lang['InstallationFinished']='Instalação concluída. Pode agora visitar o seu site. Se ocorreram erros, terá de os corrigir ou iniciar novamente o processo de instalação.<p />Lembre-se de que terá de proteger o seu ficheiro de configuração e apagar este script (<b>install.php</b>)!';
$Lang['WelcomePage']='Ir para a página de boas-vindas';
$Lang['Error']='Erro';
$Lang['Error1']='Camada de base de dados desconhecida!';
$Lang['Error2']="Não foi possível ligar à base de dados. Verifique as suas definições!";
$Lang['Error3a']="Não foi possível criar a(s) seguinte(s) tabela(s): ";
$Lang['Error3b']=". Lembre-se de que esta instalação não remove nenhuma das tabelas existentes. Talvez precise de apagar algumas ou alterar o prefixo das tabelas!";
$Lang['Error4']='Não foi possível criar o utilizador inicial!';
$Lang['Error5']='Não foi possível guardar o ficheiro de configuração. Pode guardá-lo manualmente. Eis o conteúdo do ficheiro <b>include/config.php</b>:';
$Lang['Error6']='Consultas SQL falhadas: ';
$Lang['WarningInstall1']='O ficheiro de configuração <b>include/config.php</b> não existe ou não tem permissão de escrita! Neste caso, terá de guardar o ficheiro de configuração gerado automaticamente e colocá-lo manualmente. Se tiver acesso à shell, pode criar este ficheiro e alterar as suas permissões com este comando: <p /><code>touch include/config.php && chmod 666 include/config.php</code>';
$Lang['WarningInstall2']="O diretório de registos <b>log/</b> não tem permissão de escrita! Precisa dela, caso contrário não poderá atualizar no futuro. Pode resolver isto com o seguinte comando: <p><code>chmod 777 log</code></p>Ou, se não quiser definir permissões globais para todo o diretório, deve criar e definir as permissões de três ficheiros: <b>log/common.log</b> (escrita), <b>log/chat.log</b> (escrita), <b>log/VERSION.txt</b> (leitura/escrita), o que pode fazer escrevendo:<p /><code>touch log/common.log && chmod 622 log/common.log<br />touch log/chat.log && chmod 666 log/chat.log<br />touch log/VERSION.txt && chmod 666 log/VERSION.txt</code>";
$Lang['Install']='Instalar';
$Lang['Update']='Atualizar';
$Lang['InstallMode']='Modo de instalação';
$Lang['DatabaseType']='Tipo de base de dados';
$Lang['DatabaseHost']='Servidor';
$Lang['DatabaseUser']='Utilizador da base de dados';
$Lang['DatabasePass']='Palavra-passe';
$Lang['DatabaseName']='Nome da base de dados';
$Lang['DatabasePrefix']='Prefixo das tabelas (*NÃO* pode conter espaços)';
$Lang['DatabaseCreate']='Criar base de dados (o utilizador tem de ter permissões suficientes)';
$Lang['InitialCreate']='Criar utilizador inicial (recomendado)';
$Lang['CreateWorld']='Criar mundo';
$Lang['CreateItems']='Criar objetos';
$Lang['CreateTables']='Criar as tabelas necessárias';
$Lang['DatabaseSettings']='Definições da base de dados';
$Lang['InitialSettings']='Configuração inicial';
$Lang['Install default .htaccess file']='';
