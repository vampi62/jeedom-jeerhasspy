<?php
class Model {

	public static function loadCom($uri,$headers=[],$data=null,$timeout=5) {
		$_url = config::byKey('rhasspyAddr', 'jeerhasspy');
		$_port = config::byKey('rhasspyPort', 'jeerhasspy');
		$requestHttp = new com_http("http://".$_url.":".$_port . "" . $uri);
		$requestHttp->setLogError(false);
		$requestHttp->setPost($data);
		$requestHttp->setHeader($headers);
		$result=$requestHttp->exec($timeout);
		if ($result == "") {
			return "";
		}
		log::add('jeerhasspy','debug',$requestHttp->getUrl());
		log::add('jeerhasspy','debug',$result);
		return $result;
	}

	public static function trainRhasspyModel() {
		try {
			$response = self::loadCom('/api/train',[],["language" => "fr"], 60);
			log::add('jeerhasspy', 'debug', "Réentraînement terminé");
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors du réentraînement : " . $e);
		}
	}

	public static function getRhasspySentences() {
		try {
			$response = self::loadCom('/api/sentences', ["Content-Type: application/json"]);
			$sentences = str_replace("√®", "è", $response);
			$pattern = '/\[([^\]]+)\]\s*([\s\S]*?)(?=\n\n|\Z)/';
			preg_match_all($pattern, $sentences, $matches);
			$result = [];
			foreach ($matches[1] as $index => $key) {
				$result[$key] = explode("\n", $matches[2][$index]);
			}
			return $result;
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de la récupération des sentences : " . $e);
			return [];
		}
	}

	public static function getRhasspySlots() {
		try {
			$response = self::loadCom('/api/slots', ["Content-Type: application/json"]);
			$slots = $response;
			$slots = str_replace("√®", "è", $slots);
			return json_decode($slots, true);
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de la récupération des slots : " . $e);
			return [];
		}
	}

	public static function getRhasspyCustomWord() {
		try {
			$response = self::loadCom('/api/custom-words', ["Content-Type: text/plain"]);
			// Convertir la réponse en hexadécimal pour trouver les sauts de ligne (0a), utiliser explode(/n) ne fonctionne pas donc on passe par hex2bin
			$texte_encode_hex = bin2hex($response);
			$words = explode('0a', $texte_encode_hex);
			$phoneme_dict = [];
			foreach ($words as $ligne) {
				// Diviser chaque ligne en un mot et son phonème en utilisant l'espace comme séparateur
				$elements = explode("20", $ligne);
				
				// Si l'élément est vide, passez au suivant
				if (count($elements) < 2) {
					continue;
				}
			
				// Afficher le mot et son phonème
				$mot = $elements[0];
				$phoneme = implode("20", array_slice($elements, 1));
				$phoneme_dict[hex2bin($mot)] =hex2bin($mot) . " " . hex2bin($phoneme);
			}
			return $phoneme_dict;
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de la récupération des mots personnalisés : " . $e);
			return [];
		}
	}

	public static function setRhasspySlots($data) {
		try {
			$response = self::loadCom('/api/slots?overwrite_all=true', ["Content-Type: application/json"], json_encode($data));
			log::add('jeerhasspy', 'debug', "Slots ajoutés");
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de l'ajout des slots : " . $e);
		}
	}

	public static function setRhasspySentences($data) {
		try {
			log::add('jeerhasspy', 'debug', $data);
			$response = self::loadCom('/api/sentences', [], $data);
			log::add('jeerhasspy', 'debug', "Sentences ajoutées");
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de l'ajout des sentences : " . $e);
		}
	}

	public static function setRhasspyCustomWord($data) {
		try {
			$response = self::loadCom('/api/custom-words', [], implode("\n", $data));
			log::add('jeerhasspy', 'debug', "Mots personnalisés ajoutés");
		} catch (Exception $e) {
			log::add('jeerhasspy', 'debug', "Une erreur s'est produite lors de l'ajout des mots personnalisés : " . $e);
		}
	}

	public static function buildModel($formData) {
		log::add('jeerhasspy', 'debug', 'buildModel');
		$newIntent = "";
		$jeedomEquipement = utils::o2a(eqLogic::all());
		$templateJeedomIntent = [
			"nbInteractQuery" => "0",
			"name" => "",
			"group" => "rhasspybuild",
			"enable" => "1",
			"display" => ["icon" => ""],
			"query" => "",
			"options" => ["mustcontain" => "", "synonymes" => "", "waitBeforeReply" => "", "convertBinary" => "", "exclude_regexp" => "", "allowSyntaxCheck" => "0"],
			"reply" => "",
			"person" => "",
			"comment" => "",
			"filtres" => ["type" => ["info" => "0", "action" => "1"], "subtype" => ["numeric" => "1", "binary" => "1", "string" => "0", "other" => "0", "slider" => "0", "message" => "0", "color" => "0", "select" => "0"], "unite" => ["none" => "1", "mn" => "0", "V" => "0", "%" => "0", "W" => "0", "ms" => "0", "s" => "0", "°C" => "0", "dBm" => "0", "A" => "0", "WH" => "0", "kW" => "0", "kWh" => "0", "€/L" => "0", "€" => "0", "Km" => "0", "mm" => "0", "km/h" => "0", "ppm " => "0", "Hpa" => "0", "°" => "0", "m" => "0", "mm/h" => "0", "w/m²" => "0", "lux" => "0", "Pa" => "0", "mV" => "0", "lqi" => "0", "MB" => "0", "TB" => "0", "sec" => "0", "Mo" => "0"], "object" => [], "plugin" => [], "category" => ["noCategory" => "0", "heating" => "0", "security" => "0", "energy" => "0", "light" => "0", "opening" => "0", "automatism" => "0", "multimedia" => "0", "default" => "0"], "visible" => ["object" => "1", "eqlogic" => "1", "cmd" => "1"], "eqLogic_id" => "all"],
			"actions" => ["cmd" => []]
		];
		log::add('jeerhasspy', 'debug', 'recuperation des données rhasspy');
		$rhasspySlot = self::getRhasspySlots();
		$rhasspyIntent = self::getRhasspySentences();
		$rhasspyCustomWord = self::getRhasspyCustomWord();
		
		log::add('jeerhasspy', 'debug', 'compilation des données rhasspy');
		foreach ($formData as $key => $value) {
			$rhasspyIntent[$key] = [];
			$rhasspyIntent[$key][] = "commande = (" . implode(' | ', $value["ordre"]) . "){commande}";
			$rhasspyIntent[$key][] = "equipement = ($" . $key . "_equipement){equipement}";
			$rhasspyIntent[$key][] = "objet = ($" . $key . "_objet){objet}";
			$rhasspyIntent[$key][] = "phrase_demande = (" . str_replace("] [", "|", implode(' ', $value["phrase"])) . ")";
			$rhasspyIntent[$key][] = "<phrase_demande>";
			foreach ($value['custom_word'] as $i => $word) {
				$rhasspyCustomWord[$i] = $word;
			}
			$rhasspySlot[$key . '_objet'] = [];
			foreach ($value['objet'] as $i => $objet) {
				$rhasspySlot[$key . '_objet'][] = $objet;
			}
			$rhasspySlot[$key . '_equipement'] = [];
			foreach ($value['equipement'] as $i => $equipement) {
				$rhasspySlot[$key . '_equipement'][] = $equipement;
			}
		}
		
		// compilation des intents dans une chaine de caractère
		foreach ($rhasspyIntent as $key => $value) {
			$newIntent .= "[".$key."]\n";
			if (is_array($value)) {
				foreach ($value as $line) {
					$newIntent .= $line."\n";
				}
			} else {
				$newIntent .= $value."\n";
			}
			$newIntent .= "\n";
		}

		log::add('jeerhasspy', 'debug', 'envoi des données vers rhasspy');
		self::setRhasspySentences($newIntent);
		self::setRhasspySlots($rhasspySlot);
		self::setRhasspyCustomWord($rhasspyCustomWord);

		log::add('jeerhasspy', 'debug', 'retrain du model rhasspy');
		self::trainRhasspyModel();

		log::add('jeerhasspy', 'debug', 'recuperation des données jeedom');
		$interactionsJeedom = utils::o2a(interactDef::all());
		foreach ($interactionsJeedom as $interactionJeedom) {
			$interactionJeedom['nbInteractQuery'] = count(interactQuery::byInteractDefId($interactionJeedom['id']));
			$interactionJeedom['nbEnableInteractQuery'] = count(interactQuery::byInteractDefId($interactionJeedom['id'], true));
			if (isset($interactionJeedom['link_type']) && $interactionJeedom['link_type'] == 'cmd' && $interactionJeedom['link_id'] != '') {
				$link_id = '';
				foreach (explode('&&', $interactionJeedom['link_id']) as $cmd_id) {
					$cmd = cmd::byId($cmd_id);
					if (is_object($cmd)) {
						$link_id .= cmd::cmdToHumanReadable('#' . $cmd->getId() . '# && ');
					}
				}
				$interactionJeedom['link_id'] = trim(trim($link_id), '&&');
			}
		}
		log::add('jeerhasspy', 'debug', json_encode($interactionsJeedom));
	}
}
?>