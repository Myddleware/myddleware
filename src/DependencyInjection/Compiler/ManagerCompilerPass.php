<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ManagerCompilerPass implements CompilerPassInterface
{
	
	protected $classes = array(
									'DocumentManager' => 'premium', 
									'ToolsManager' => 'premium', 
									'SolutionManager' => 'standard', 
									'RuleManager' => 'standard', 
									'FormulaFunctionManager' => 'standard', 
									'FormulaManager' => 'standard', 
									'JobManager' => 'standard', 
									'LoadExternalListManager' => 'standard', 
									'NotificationManager' => 'standard', 
									'TemplateManager' => 'standard', 
									'UpgradeManager' => 'standard', 
								);
	
    public function process(ContainerBuilder $container)
    {
		// Manage each class depending of its type
		foreach($this->classes as $class => $type) {
			$customClass = $class.'Custom';
			// For premium classes
			if ($type == 'premium') {
				$premiumClass = $class.'Premium';
				// If the custom class exists we use it as priority 1
				if (class_exists("App\Custom\Manager\\$customClass")) {
					// Change service name
					$definition = $container->getDefinition("App\Manager\\$class");
					$definition->setClass("App\Custom\Manager\\$customClass");

				// If the premium class exists we use it as priority 2
				} elseif (class_exists("App\Premium\Manager\\$premiumClass")) {
					// Change service name
					$definition = $container->getDefinition("App\Manager\\$class");
					$definition->setClass("App\Premium\Manager\\$premiumClass");
				} 
			// For non premium classes
			} else {
				// If the custom class exists we use it 
				if (class_exists("App\Custom\Manager\$customClass")) {
					// Change service name
					$definition = $container->getDefinition("App\Manager\\$class");
					$definition->setClass("App\Custom\Manager\\$customClass");
				} 
			}
		}
    }
}
