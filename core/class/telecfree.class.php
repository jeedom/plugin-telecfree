<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'telecfree', 'config', 'telecfree');
class telecfree extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function preUpdate() {
		if ($this->getConfiguration('codetelec') == '') {
            throw new Exception(__('Le code télécommande doit être renseigné. Vous pouvez trouver le code dans les paramètres de votre Freebox Player.',__FILE__));
		}		
		if ($this->getConfiguration('ipplayer') == '') {
            throw new Exception(__('L`adresse IP du player doit être renseignée, par défaut 192.168.1.2.',__FILE__));
        }
    }
	
	public function getGroups() {
       return array('basic', 'numero', 'chaines', 'chainesperso', 'raccourcis');
    }
	
	public function commandByName($name) {
        global $listCmdtelecfree;
        foreach ($listCmdtelecfree as $cmd) {
           if ($cmd['name'] == $name)
            return $cmd;
        }
        return null;
    }
	
	public function addCommand($cmd) {
       if (cmd::byEqLogicIdCmdName($this->getId(), $cmd['name']))
            return;
            
       if ($cmd) {
            $telecfreeCmd = new telecfreeCmd();
            $telecfreeCmd->setName(__($cmd['name'], __FILE__));
            $telecfreeCmd->setEqLogic_id($this->id);
		    $telecfreeCmd->setConfiguration('request', $cmd['configuration']['request']);
		    $telecfreeCmd->setConfiguration('parameters', $cmd['configuration']['parameters']);
		    $telecfreeCmd->setConfiguration('group', $cmd['group']);
            $telecfreeCmd->setType($cmd['type']);
            $telecfreeCmd->setSubType($cmd['subType']);
            $telecfreeCmd->setIsVisible($cmd['isVisible']);
			//if ($cmd['icon'] != '')
			//	$telecfreeCmd->setDisplay('icon', '<i class=" '.$cmd['icon'].'"></i>');
		    $telecfreeCmd->save();
       }
    }
    
    public function addCommandByName($name, $cmd_name) {
       if ($cmd = $this->commandByName($name)) {
			$this->addCommand($cmd);
       }
    }

    public function removeCommand($name) {
        if (($cmd = cmd::byEqLogicIdCmdName($this->getId(), $name)))
			$cmd->remove();
    }
    
    public function addCommands($groupname) {
        global $listCmdtelecfree;
        
        foreach ($listCmdtelecfree as $cmd) {
           if ($cmd['group'] == $groupname)
				$this->addCommand($cmd);
        }        
    }
    
    public function removeCommands($groupname) {
        global $listCmdtelecfree;
        
        foreach ($listCmdtelecfree as $cmd) {
           if ($cmd['group'] == $groupname)
				$this->removeCommand($cmd['name']);
        }
    }
	
	
    public function preSave() {
		if (!$this->getId()) {
          return;
		}
		if ($this->getConfiguration('has_basic') == 1) {
			$this->addCommands('basic');
        } else {
            $this->removeCommands('basic');
        }
		if ($this->getConfiguration('has_raccourcis') == 1) {
			$this->addCommands('raccourcis');
        } else {
            $this->removeCommands('raccourcis');
        }
		if ($this->getConfiguration('has_chaines') == 1) {
			$this->addCommands('chaines');
			$this->addCommands('chainesperso');
        } else {
            $this->removeCommands('chaines');
            $this->removeCommands('chainesperso');
        }
        $this->removeCommands('numero');
    }
	
    public function postSave() {
	}
    

	public function postInsert() {
    }
	
	public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
            return '';
        }
		if (!$this->hasRight('r')) {
			return '';
		}
        $_version = jeedom::versionAlias($_version);
		$replace = array(
			'#id#' => $this->getId(),
			'#info#' => (isset($info)) ? $info : '',
			'#name#' => ($this->getIsEnable()) ? $this->getName() : '<del>' . $this->getName() . '</del>',
			'#eqLink#' => $this->getLinkToConfiguration(),
			'#action#' => (isset($action)) ? $action : '',
			'#background_color#' => $this->getBackgroundColor($_version),
		);
		
		// Charger les template de groupe
        $groups_template = array();
        $group_names = $this->getGroups();
		foreach ($group_names as $group) {
            $groups_template[$group] = getTemplate('core', $_version, $group, 'telecfree');
            $replace['#group_'.$group.'#'] = '';
        }
		
		// Afficher les commandes dans les bonnes templates
        // html_groups: permet de gérer le #cmd# dans la template.
        $html_groups = array();
        if ($this->getIsEnable()) {
            foreach ($this->getCmd() as $cmd) {
                $cmd_html = ' ';
                $group    = $cmd->getConfiguration('group');
                if ($cmd->getIsVisible()) {
				
					if ($cmd->getType() == 'info') {
						log::add('telecfree','debug','cmd = info');
						$cmd_html = $cmd->toHtml();
					} 
					else {
						$cmd_template = getTemplate('core', $_version, $group.'_cmd', 'telecfree');        
						$cmd_replace = array(
							'#id#' => $cmd->getId(),
							'#name#' => ($cmd->getDisplay('icon') != '') ? $cmd->getDisplay('icon') : $cmd->getName(),
							'#oriname#' => $cmd->getName(),
						);
						
						// Construction du HTML pour #cmd#
						$cmd_html = template_replace($cmd_replace, $cmd_template);
					}
                    if (isset($html_groups[$group]))
					{
						$html_groups[$group]++;
						$html_groups[$group] .= $cmd_html;
					} else {
						$html_groups[$group] = $cmd_html; 
					}    
                } 
                $cmd_replace = array(
                    '#'.strtolower($cmd->getName()).'#' => $cmd_html,
                    );
                $groups_template[$group] = template_replace($cmd_replace, $groups_template[$group]);
            }
        }
        
        // Remplacer #group_xxx de la template globale
        $replace['#cmd'] = "";
        $keys = array_keys($html_groups);
		foreach ($html_groups as $group => $html_cmd) {      
            $group_template =  $groups_template[$group]; 
            $group_replace = array(
                '#cmd#' => $html_cmd,
            );
            $replace['#group_'.$group.'#'] .= template_replace($group_replace, $group_template);
        }
		$parameters = $this->getDisplay('parameters');
        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $replace['#' . $key . '#'] = $value;
            }
        }
	
        return template_replace($replace, getTemplate('core', $_version, 'eqLogic', 'telecfree'));
    }
	
	public static function event() {
		$cmd =  telecfreeCmd::byId(init('id'));
	   
		if (!is_object($cmd)) {
			throw new Exception('Commande ID virtuel inconnu : ' . init('id'));
		}
	   
		$value = init('value');
       
		if ($cmd->getEqLogic()->getEqType_name() != 'telecfree') {
			throw new Exception(__('La cible de la commande telecfree n\'est pas un équipement de type telecfree', __FILE__));
		}
		   
		$cmd->event($value);
	   
		$cmd->setConfiguration('valeur', $value);
		log::add('telecfree','debug','set:'.$cmd->getName().' to '. $value);
		$cmd->save();
		
   }
   
}

class telecfreeCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function preSave() {
        if ($this->getConfiguration('request') == '') {
            throw new Exception(__('La requete ne peut etre vide',__FILE__));
		}
		
		
    }

    public function execute($_options = null) {
    	$telecfree = $this->getEqLogic();
        $teleccode = $telecfree->getConfiguration('codetelec');
        $playerip = $telecfree->getConfiguration('ipplayer');
		$key = $telecfree->getConfiguration('key');
		if ($this->type == 'action') {
				$type=$this->getConfiguration('type');
				$command=$this->getConfiguration('parameters');
				
				// Raccourcis vers les chaines TV
				$rest = substr($command, 0, -3);  
				if ($rest=='chaine_') {
					if ($command=='chaine_001') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
					}
					if ($command=='chaine_002') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
					}
					if ($command=='chaine_003') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=3');
					}
					if ($command=='chaine_004') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=4');
					}
					if ($command=='chaine_005') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=5');
					}
					if ($command=='chaine_006') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=6');
					}
					if ($command=='chaine_007') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=7');
					}
					if ($command=='chaine_008') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=8');
					}
					if ($command=='chaine_009') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=9');
					}
					if ($command=='chaine_010') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=0');
					}
					if ($command=='chaine_011') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
					}
					if ($command=='chaine_012') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
					}
					if ($command=='chaine_013') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=3');
					}
					if ($command=='chaine_014') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=4');
					}
					if ($command=='chaine_015') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=5');
					}
					if ($command=='chaine_016') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=6');
					}
					if ($command=='chaine_017') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=7');
					}
					if ($command=='chaine_018') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=8');
					}
					if ($command=='chaine_019') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=9');
					}
					if ($command=='chaine_020') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=0');
					}
					if ($command=='chaine_021') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
					}
					if ($command=='chaine_022') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
					}
					if ($command=='chaine_023') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=3');
					}
					if ($command=='chaine_024') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=4');
					}
					if ($command=='chaine_025') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=5');
					}
					if ($command=='chaine_026') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=6');
					}
					if ($command=='chaine_027') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=7');
					}
					if ($command=='chaine_028') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=8');
					}
					if ($command=='chaine_031') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=3');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
					}
					if ($command=='chaine_032') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=3');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=2');
					}
					if ($command=='chaine_039') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=3');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=9');
					}
					if ($command=='chaine_048') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=4');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=8');
					}
					if ($command=='chaine_143') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=1');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=4');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=3');
					}
				}
				// Raccourcis personnalisables vers les chaines TV
				else if ($rest=='chaineperso_') {
					$key1 = substr($command, -3, 1);  
					$key2 = substr($command, -2, 1);  
					$key3 = substr($command, -1, 1);  
					$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key='.$key1);
					usleep(500000);
					$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key='.$key2);
					usleep(500000);
					$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key='.$key3);
				}
				
				// Raccourcis vers les principales applications (TV, replay, radios...)
				else if ($command=='981' or $command=='982' or $command=='983' or $command=='984'
					or $command=='985' or $command=='986') {
					$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=home');
					sleep(4);
					$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=home');
					usleep(500000);
					if ($command=='981') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=ok');
						sleep(5);
					}
					if ($command=='982') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						sleep(1);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=down');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=down');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=ok');
					}
					if ($command=='983') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						sleep(1);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=ok');
					}
					if ($command=='984') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						sleep(1);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=down');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=down');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=ok');
					}
					if ($command=='985') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						sleep(1);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=ok');
					}
					if ($command=='986') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=right');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=ok');
					}
				}
				// Guide des programmes
				else if ($command=='987') {
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=green');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=down');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=ok');
						sleep(2);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=down');
						usleep(500000);
						$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key=ok');
				}
				
				// Commande standard de la telecommande
				else {
					$commande = file_get_contents('http://'.$playerip.'/pub/remote_control?code='.$teleccode.'&key='.$command);
				}
		}
    }
		


    /*     * **********************Getteur Setteur*************************** */
}
?>