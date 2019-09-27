<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com	
 
 This file is part of Myddleware.
 
 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
<link type="text/css" href="<?php echo $urlBase; ?>/web/css/compiled/main_layout_2.css" rel="stylesheet">
*********************************************************************************/
?>
<?php $urlBase = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].$_SERVER['BASE']; ?>

<html class="" lang="us">
<head>
<link type="text/css" href="<?php echo $urlBase; ?>/bundles/regle/css/account.css" rel="stylesheet">
<link type="text/css" href="<?php echo $urlBase; ?>/bundles/regle/css/layout.css" rel="stylesheet">
<link type="text/css" href="<?php echo $urlBase; ?>/css/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<style type="text/css"> 

body {
	background:  url(<?php echo $urlBase.'/bundles/regle/images/template/fd.jpg' ?>) repeat-y; 
	background-color: #9DC0E1;
}
#logo {
	background: url(<?php echo $urlBase.'//bundles/regle/images/template/logo.png' ?>) no-repeat; 
	background-size: 158px;
	height: 113px;
	width: 160px;
	display: inline-block;
	margin-top: 25px;
	margin-left: 10%;
}
#submenu th {
    background: #ececec none repeat scroll 0 0;
    height: 30px;
    width: 500px;
    padding: 2px;
    text-align: center;
	box-sizing: border-box;
}

table, th, td {
    border: 5px solid white;
}

</style>

<div id="myd_top"></div>
<div id="logo"></div>
<div id="myd_title">Myddleware installation</div>
<section>
<div id="user_account">
<div id="user_info">
<table>
<tr>
<td>
<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL); 
require_once dirname(__FILE__).'/../var/SymfonyRequirements.php';
$lineSize = 70;
$symfonyRequirements = new SymfonyRequirements();

// Specific requierement for Myddleware
$symfonyRequirements->addRequirement(
	is_writable(__DIR__.'/../app/config/parameters.yml'),
	'config/parameters.yml file must be writable',
	'Change the permissions "<strong>config/parameters.yml</strong>" file so that the web server can write into it.'
);
$symfonyRequirements->addRecommendation(
	is_writable(__DIR__.'/../app/config/public/parameters_public.yml'),
	'config/public/parameters_public.yml file should be writable',
	'Change the permissions "<strong>config/public/parameters_public.yml</strong>" file so that the web server can write into it.'
);
$symfonyRequirements->addRecommendation(
	is_writable(__DIR__.'/../app/config/public/parameters_smtp.yml'),
	'config/public/parameters_smtp.yml file should be writable',
	'Change the permissions "<strong>config/public/parameters_smtp.yml</strong>" file so that the web server can write into it.'
);
// Check php version
$symfonyRequirements->addRequirement( version_compare(phpversion(), '7.1', '>='), 'Wrong php version', 'Your php version is '.phpversion().'. Myddleware is compatible only with php version 7.1 and 7.2.');
$symfonyRequirements->addRequirement( version_compare(phpversion(), '7.3', '<'), 'Wrong php version', 'Your php version is '.phpversion().'. Myddleware is compatible only with php version 7.1 and 7.2.');

$iniPath = $symfonyRequirements->getPhpIniConfigPath();

echo_title('Myddleware Requirements Checker');

echo '> PHP is using the following php.ini file:'.'<BR>';
if ($iniPath) {
    echo_style('green', '  '.$iniPath);
} else {
    echo_style('warning', '  WARNING: No configuration file (php.ini) used by PHP!');
}

echo '<BR>'.'<BR>';

echo '> Checking Myddleware requirements:'.'<BR>'.'  ';

$messages = array();
foreach ($symfonyRequirements->getRequirements() as $req) {
    /** @var $req Requirement */
    if ($helpText = get_error_message($req, $lineSize)) {
        echo_style('red', 'E');
        $messages['error'][] = $helpText;
    } else {
        echo_style('green', '.');
    }
}

$checkPassed = empty($messages['error']);

foreach ($symfonyRequirements->getRecommendations() as $req) {
    if ($helpText = get_error_message($req, $lineSize)) {
        echo_style('orange', 'W');
        $messages['warning'][] = $helpText;
    } else {
        echo_style('green', '.');
    }
}

if ($checkPassed) {
    echo_block('success', 'OK', 'Your system is ready to run Myddleware');
} else {
    echo_block('error', 'ERROR', 'Your system is not ready to run Myddleware');

    echo_title('Fix the following mandatory requirements', 'red');

    foreach ($messages['error'] as $helpText) {
        echo ' * '.$helpText.'<BR>';
    }
}

if (!empty($messages['warning'])) {
    echo_title('Optional recommendations to improve your setup', 'orange');

    foreach ($messages['warning'] as $helpText) {
        echo ' * '.$helpText.'<BR>';
    }
}

echo '<BR>';
echo_style('title', 'Note');
echo '  The command console could use a different php.ini file'.'<BR>';
echo_style('title', '~~~~');
echo '  than the one used with your web server. To be on the'.'<BR>';
echo_style('title', '~~~~');
echo '  safe side, please check the requirements from your web'.'<BR>';
echo_style('title', '~~~~');
echo '  server using the command from your Myddleware directory'.'<BR>';
echo_style('title', '~~~~');
echo '  '; 
echo_style('green', 'php bin/symfony_requirements');
echo '<BR>';
echo '<BR>';

function get_error_message(Requirement $requirement, $lineSize)
{
    if ($requirement->isFulfilled()) {
        return;
    }

    $errorMessage  = wordwrap($requirement->getTestMessage(), $lineSize - 3, '<BR>'.'   ').'<BR>';
    $errorMessage .= '   > '.wordwrap($requirement->getHelpText(), $lineSize - 5, '<BR>'.'   > ').'<BR>';

    return $errorMessage;
}

function echo_title($title, $style = null)
{
    $style = $style ?: 'title';

    echo '<BR>';
    echo_style($style, $title.'<BR>');
    echo_style($style, str_repeat('~', strlen($title)).'<BR>');
    echo '<BR>';
}

function echo_style($style, $message)
{
	echo '<font color="'.$style.'">'.$message.'</font>';
}

function echo_block($style, $title, $message)
{
    $message = ' '.trim($message).' ';
    $width = strlen($message);

    echo '<BR>'.'<BR>';

    echo_style($style, str_repeat(' ', $width).'<BR>');
    echo_style($style, str_pad(' ['.$title.']',  $width, ' ', STR_PAD_RIGHT).'<BR>');
    echo_style($style, str_pad($message,  $width, ' ', STR_PAD_RIGHT).'<BR>');
    echo_style($style, str_repeat(' ', $width).'<BR>');
}
  
?>
<button class="btn-mydinv" type="button" onClick="window.location.reload()"></span> Re check</button>
</td>
<td>
<?php 
if (!$checkPassed) {
	 echo_block('error', 'ERROR', 'Fix your configuration error then you wil be abble to install Myddleware');
}
else {
	// Check if the user try to install Myddleware
	$error = false;
	// Start of the Symfony Kernel
	require_once(__DIR__ . "/../app/AppKernel.php");	
	$kernel = new \AppKernel("prod", true);	
	
	$binDir = $kernel->getRootDir().'/../bin/';

	// Get current Myddleware parameters
	$myddlewareParameters = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($kernel->getRootDir() .'/config/parameters.yml'));
	$myddlewareParametersPublic = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($kernel->getRootDir() .'/config/public/parameters_public.yml'));
	$databaseConnection = 0;
	$userCreated = 0;

	// Add secret, require for Symfony
	if ($myddlewareParameters['parameters']['secret'] == 'ThisTokenIsNotSoSecretChangeIt') {
		$myddlewareParameters['parameters']['secret']= md5(rand(0,10000).date('YmdHis').'myddlewa');		
		$new_yaml = \Symfony\Component\Yaml\Yaml::dump($myddlewareParameters, 4);
		file_put_contents($kernel->getRootDir() .'/config/parameters.yml', $new_yaml);
	}	
	
	// Before we open the form for the installation we check the access to the database 
	if (
			empty($_POST['install_status'])
		&& !empty($myddlewareParameters['parameters']['database_name'])
	) {
		// Try to connect to the database with the new parameters
		try {
			$pdo = new \PDO('mysql:host='.$myddlewareParameters['parameters']['database_host'].';port='.$myddlewareParameters['parameters']['database_port'].';dbname='.$myddlewareParameters['parameters']['database_name'], $myddlewareParameters['parameters']['database_user'], $myddlewareParameters['parameters']['database_password'],array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
			$databaseConnection = 1;
		} catch(PDOException $ex){
		}
	}
	elseif (
			!empty($_POST['install_status'])
		&& $_POST['install_status'] == 'installation'
		&& empty($myddlewareParameters['block_install'])
	) {
		// Check every filed are OK
		if (empty($_POST['database_host'])) {
			echo_block('error', 'ERROR', "Please, add a database host");
			$error = true;
		}
		elseif (empty($_POST['database_name'])) {
			echo_block('error', 'ERROR', "Please, add a database name");
			$error = true;
		}
		elseif (empty($_POST['database_user'])) {
			echo_block('error', 'ERROR', "Please, add a database user");
			$error = true;
		}
		elseif (empty($_POST['myddleware_username'])) {
			echo_block('error', 'ERROR', "Please, add a user for Myddleware");
			$error = true;
		}
		elseif (empty($_POST['myddleware_password'])) {
			echo_block('error', 'ERROR', "Please, add a password for Myddleware user");
			$error = true;
		}
		elseif (empty($_POST['myddleware_user_email'])) {
			echo_block('error', 'ERROR', "Please, add an email for Myddleware user");
			$error = true;
		}
		elseif ($_POST['myddleware_password'] != $_POST['myddleware_password2']) {
			echo_block('error', 'ERROR', "Passwords are differents for the Myddleware user.");
			$error = true;
		}
		
		if (!$error) {
			try {
				// Save database access to the file parameters.yml
				$myddlewareParameters['parameters']['database_host'] 	= $_POST['database_host'];
				$myddlewareParameters['parameters']['database_port'] 	= $_POST['database_port'];
				$myddlewareParameters['parameters']['database_name'] 	= $_POST['database_name'];
				$myddlewareParameters['parameters']['database_user'] 	= $_POST['database_user'];
				$myddlewareParameters['parameters']['database_password']= $_POST['database_password'];				
				$myddlewareParameters['parameters']['block_install']	= 1;				
				$new_yaml = \Symfony\Component\Yaml\Yaml::dump($myddlewareParameters, 4);
				file_put_contents($kernel->getRootDir() .'/config/parameters.yml', $new_yaml);
				
				/* // Refresh boostrap
				$process = new \Symfony\Component\Process\Process($myddlewareParametersPublic['parameters']['php']['executable'].' '. $binDir.'/../vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php');
				$process->run();
				// executes after the command finishes
				if (!$process->isSuccessful()) {
					throw new Symfony\Component\Process\Exception\ProcessFailedException($process);
				} */
				
				$process = new \Symfony\Component\Process\Process($myddlewareParametersPublic['parameters']['php']['executable'].' '. $binDir .'/console cache:clear --env='. $kernel->getEnvironment());
				$process->run();
				// executes after the command finishes
				if (!$process->isSuccessful()) {
					throw new Symfony\Component\Process\Exception\ProcessFailedException($process);
				}
				
				// Check the database connection
				try {
				
					// Read again Myddleware parameters with the new values
					$myddlewareParametersNew = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($kernel->getRootDir() .'/config/parameters.yml'));
					$pdo = new \PDO('mysql:host='.$myddlewareParametersNew['parameters']['database_host'].';port='.$myddlewareParametersNew['parameters']['database_port'].';dbname='.$myddlewareParametersNew['parameters']['database_name'], $myddlewareParametersNew['parameters']['database_user'], $myddlewareParametersNew['parameters']['database_password'],array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
					$databaseConnection = 1;
				} catch(PDOException $ex){
					throw new \Exception ("Can't open the database : ". $ex->getMessage( ));
				}

				// If database OK
				$pdo->beginTransaction();
				// Database creation
				$process = new \Symfony\Component\Process\Process($myddlewareParametersPublic['parameters']['php']['executable'].' '. $binDir .'/console doctrine:schema:update --force --env='. $kernel->getEnvironment());
				$process->run();
				// executes after the command finishes
				if (!$process->isSuccessful()) {
					throw new Symfony\Component\Process\Exception\ProcessFailedException($process);
				}
				
				// Init table (instert standard data)
				$process = new \Symfony\Component\Process\Process($myddlewareParametersPublic['parameters']['php']['executable'].' '. $binDir .'/console doctrine:fixtures:load --append --env='. $kernel->getEnvironment());
				$process->run();
				// executes after the command finishes
				if (!$process->isSuccessful()) {
					throw new Symfony\Component\Process\Exception\ProcessFailedException($process);
				}

				$process = new \Symfony\Component\Process\Process($myddlewareParametersPublic['parameters']['php']['executable'].' '. $binDir .'/console fos:user:create '.$_POST['myddleware_username'].' '.$_POST['myddleware_user_email'].' '.$_POST['myddleware_password'].' --env='. $kernel->getEnvironment());
				$process->run();
				// executes after the command finishes
				if (!$process->isSuccessful()) {
					throw new Symfony\Component\Process\Exception\ProcessFailedException($process);
				}
				$userCreated = 1;
				
				$process = new \Symfony\Component\Process\Process($myddlewareParametersPublic['parameters']['php']['executable'].' '. $binDir .'/console fos:user:promote '.$_POST['myddleware_username'].' ROLE_ADMIN --env='. $kernel->getEnvironment());
				$process->run();
				// executes after the command finishes
				if (!$process->isSuccessful()) {
					throw new Symfony\Component\Process\Exception\ProcessFailedException($process);
				}
				$pdo->commit();
			} catch (Exception $e) {
				$error = true;			
				// Get current Myddleware parameters
				$myddlewareParameters = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($kernel->getRootDir() .'/config/parameters.yml'));
				$myddlewareParameters['parameters']['database_host'] 	 = '';
				$myddlewareParameters['parameters']['database_port'] 	 = '';
				$myddlewareParameters['parameters']['database_name'] 	 = '';
				$myddlewareParameters['parameters']['database_user'] 	 = '';
				$myddlewareParameters['parameters']['database_password']= '';	
				$myddlewareParameters['parameters']['block_install']	 = 0;						
				$clearYaml = \Symfony\Component\Yaml\Yaml::dump($myddlewareParameters, 4);
				file_put_contents($kernel->getRootDir() .'/config/parameters.yml', $clearYaml);
				echo_block('error','ERROR',"Failed: " . $e->getMessage());
				if (!empty($pdo)) {		
					$pdo->rollBack();
					if ($userCreated) {
						$pdo->exec("TRUNCATE TABLE users");
					}
				}
			}	
		}
	}
	?>
	<form action="installMyddleware.php" method="post">
	<?php if (!$myddlewareParameters['parameters']['block_install'])	 { ?>
		<div id="submenu">
			<table id="tab_rule">
				<tr><th colspan="2">Database access</th></tr>
			 </table>	
		</div>	
		<table>
			<tr><th width="50%">Host<span class="red">*</span>:</th>	<th width="50%"><input type="text" name="database_host" value="<?php echo (!empty($_POST['database_host']) ? $_POST['database_host'] : 'localhost'); ?>"></th></tr>
			<tr><th width="50%">Port:  									</th><th width="50%"><input type="text" name="database_port" value="<?php echo (!empty($_POST['database_port']) ? $_POST['database_port'] : ''); ?>"></th></tr>
			<tr><th width="50%">Database name<span class="red">*</span>:</th><th width="50%"><input type="text" name="database_name" value="<?php echo (!empty($_POST['database_name']) ? $_POST['database_name'] : ''); ?>"></th></tr>
			<tr><th width="50%">Username<span class="red">*</span>:		</th><th width="50%"><input type="text" name="database_user" value="<?php echo (!empty($_POST['database_user']) ? $_POST['database_user'] : ''); ?>"></th></tr>
			<tr><th width="50%">Password: 								</th><th width="50%"><input type="password" name="database_password" value=""></th></tr>
		</table>
		<BR>
		<div id="submenu">
			<table id="tab_rule">
				<tr><th colspan="2">Myddleware user</th></tr>
			 </table>	
		</div>	
		<table>
			<tr><th width="50%">Username<span class="red">*</span>:  	</th><th width="50%"><input type="text" name="myddleware_username" value="<?php echo (!empty($_POST['myddleware_username']) ? $_POST['myddleware_username'] : ''); ?>"></th></tr>
			<tr><th width="50%">Password<span class="red">*</span>:  	</th><th width="50%"><input type="password" name="myddleware_password" value=""></th></tr>
			<tr><th width="50%">Password<span class="red">*</span>:  	</th><th width="50%"><input type="password" name="myddleware_password2" value=""></th></tr>
			<tr><th width="50%">Email<span class="red">*</span>:  		</th><th width="50%"><input type="email" name="myddleware_user_email" value="<?php echo (!empty($_POST['myddleware_user_email']) ? $_POST['myddleware_user_email'] : ''); ?>"></th></tr>
		</table>
		<BR>
		<table>
			<tr><th colspan="2"><input type="submit" value="Submit"></th>
		</table>
		<input type="hidden" name="install_status" value="installation"/>
	<?php } else {  ?>	
		<div id="submenu">
			<table id="tab_rule">
				<tr><th colspan="2">Myddleware is installed</th></tr>
				<tr><th colspan="2">You can access to Myddleware here </th></tr>
				<tr><th colspan="2"><a href="<?php echo $urlBase. '/app.php';?>"><?php echo $urlBase . '/app.php';?></a></th></tr>
				<tr><th colspan="2">For security reasons, please delete the file : <?php echo $urlBase. '/installMyddleware.php';?></th></tr>				
			 </table>	
		</div>	
	<?php } ?>
	  </table>
	</form> 
	<?php
}

?>
</td>
</tr>
</table>
</div>
</div>
</section>	
<footer>
<p>© Myddleware 2014-2018</p>
<p>v<?php echo $myddlewareParameters['parameters']['myd_version'];?></p>
</footer>
</body>
</html>