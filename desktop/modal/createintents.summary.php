<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$_objet = array();
foreach ((jeeObject::all()) as $object) {
	$_objet[$object->getId()] = $object->getName();
}
// charge le fichier json "formFR.json" qui contient les formulaires
$filename = __DIR__ . '/formFR.json';
$json = file_get_contents($filename);
$configformulaire = json_decode($json, true);
$_saveFormulaire = config::byKeys(['formulaire-jeedomLumiere','formulaire-jeedomVolet'], 'jeerhasspy');

$varList = array();
$varList['_objet'] = $_objet;
sendVarToJS('configformulaire', $configformulaire);
sendVarToJS('_saveFormulaire', $_saveFormulaire);
sendVarToJS('varList', $varList);
?>
<style>
	.multiselect {
	  width: 200px;
	  display: flex;
	}

	.intentBox {
	  position: relative;
	  white-space: nowrap;
	  margin-left: 5px;
	  margin-right: 5px;
	}

	.intentBox select {
	  width: 100%;
	  font-weight: bold;
	}

	.overSelect {
	  position: absolute;
	  left: 0;
	  right: 0;
	  top: 0;
	  bottom: 0;
	}

	.checkboxes {
	  display: block;
	  margin-left: 5px;
	  margin-right: 5px;
	  white-space: nowrap;
	  border: 1px #dadada solid;
	}

	.checkboxes label {
	  display: block;
	}

	.checkboxes label:hover {
	  background-color: #1e90ff;
	}
</style>

<div id="div_alertIntentsForm"></div>
<div style="display:flex;align-items: flex-start;">
	<div id="menu" style="display:flex;flex-direction: column;">
		<div id="indexIntent">
		</div>
		<a class="btn btn-danger btn-sm roundedRight" id="bt_resetform"><i class="fas fa-undo"></i> Réinitialiser</a>
		<a class="btn btn-success btn-sm roundedRight" id="bt_trainmodel"><i class="fas fa-check-circle"></i> Sauvegarder / Ré-entraîner</a>
	</div>
	<div id="pageIntent" style="display:flex;">
	</div>
</div>


<script>
	console.log(configformulaire)
	console.log(_saveFormulaire)
	console.log(varList)
	for (key in configformulaire) {
		// ajout de l'intent dans l'index a	gauche
		menuIntent = document.createElement('div');
		menuIntent.style.display = 'flex';
		menuIntent.style.justifyContent = 'space-between';
		menuIntent.innerHTML = '<a href="#" onclick="showForm(\'' + key + '\')">' + configformulaire[key]["name"] + '</a>';
		hasSave = false;
		if (key in _saveFormulaire) {
			hasSave = true;
		}
		if ((hasSave) && ('active' in _saveFormulaire['formulaire-' + key])) {
			if ($_saveFormulaire['formulaire-' + key]['active'] == "1") {
				menuIntent.innerHTML += '<input type="checkbox" id="select' + key + '" name="' + configformulaire[key]["name"] + '" checked>';
			} else {
				menuIntent.innerHTML += '<input type="checkbox" id="select' + key + '" name="' + configformulaire[key]["name"] + '">';
			}
		} else {
			menuIntent.innerHTML += '<input type="checkbox" id="select' + key + '" name="' + configformulaire[key]["name"] + '">';
		}
		document.getElementById('indexIntent').appendChild(menuIntent);

		// ajout du formulaire
		form = document.createElement('div');
		form.id = key;
		form.style.display = 'none';
		formVue = document.createElement('div');
		for (panel in configformulaire[key]["module"]) {
			formNavIntent = document.createElement('div');
			formNavIntent.innerHTML = '<a href="#" onclick="showPanel(\'' + key + '\',\'' + panel + '\')">' + configformulaire[key]["module"][panel]["name"] + '</a>';
			formPanel = document.createElement('div');
			formPanel.id = key + panel;
			formPanel.style.display = 'none';
			switch (configformulaire[key]["module"][panel]["type"]) {
				case "select":
					formLabel = document.createElement('label');
					formLabel.innerHTML = configformulaire[key]["module"][panel]["balise"];
					formSelect = document.createElement('select');
					if (configformulaire[key]["module"][panel]["size"] > 1) {
						formSelect.multiple = true;
						formSelect.size = configformulaire[key]["module"][panel]["size"];
					}
					if (is_array(configformulaire[key]["module"][panel]["option"])) {
						for (option in configformulaire[key]["module"][panel]["option"]) {
							baliseOption = document.createElement('option');
							baliseOption.value = option;
							baliseOption.innerHTML = option;
							if ((hasSave) && (option in _saveFormulaire['formulaire-' + key][panel]) && (_saveFormulaire['formulaire-' + key][panel][option] == "1")) {
								baliseOption.selected = true;
							}
							if ((configformulaire[key]["module"][panel]["option"][option] == "1")) {
								baliseOption.selected = true;
							} 
							formSelect.appendChild(baliseOption);
						}
					} else {
						for (option in varList[configformulaire[key]["module"][panel]["option"]]) {
							baliseOption = document.createElement('option');
							baliseOption.value = varList[configformulaire[key]["module"][panel]["option"]][option];
							baliseOption.innerHTML = varList[configformulaire[key]["module"][panel]["option"]][option];
							if ((hasSave) && (option in _saveFormulaire['formulaire-' + key][panel]) && (_saveFormulaire['formulaire-' + key][panel][option] == "1")) {
								baliseOption.selected = true;
							}
							formSelect.appendChild(baliseOption);
						}
					}
					formPanel.appendChild(formLabel);
					formPanel.appendChild(formSelect);
					break;
				case "text":
					formLabel = document.createElement('label');
					formLabel.innerHTML = configformulaire[key]["module"][panel]["balise"];
					formText = document.createElement('input');
					formText.type = 'text';
					formText.value = '';
					if ((hasSave) && (panel in _saveFormulaire['formulaire-' + key]) && (_saveFormulaire['formulaire-' + key][panel] != "")) {
						formText.value = _saveFormulaire['formulaire-' + key][panel];
					}
					formPanel.appendChild(formLabel);
					formPanel.appendChild(formText);
					break;
				case "checkbox":
					formLabel = document.createElement('label');
					formLabel.innerHTML = configformulaire[key]["module"][panel]["balise"];
					formLabel.className = 'checkboxes';
					formBox = document.createElement('div');
					for (text in configformulaire[key]["module"][panel]["text"]) {
						formCheckbox = document.createElement('input');
						formCheckbox.type = 'checkbox';
						formCheckbox.value = configformulaire[key]["module"][panel]["text"][text];
						formCheckbox.innerHTML = configformulaire[key]["module"][panel]["text"][text];
						if ((hasSave) && (panel in _saveFormulaire['formulaire-' + key]) && (_saveFormulaire['formulaire-' + key][panel] != "")) {
							if (in_array(configformulaire[key]["module"][panel]["text"][text], _saveFormulaire['formulaire-' + key][panel])) {
								formCheckbox.checked = true;
							}
						}
						formBox.appendChild(formCheckbox);
					}
					formPanel.appendChild(formLabel);
					formPanel.appendChild(formBox);
					break;
				case "phrase":
					formLabel = document.createElement('label');
					formLabel.innerHTML = configformulaire[key]["module"][panel]["balise"];
					break;
				case "multitext":
					for (element in configformulaire[key]["module"][panel]["elements"]) {
						formLabel = document.createElement('label');
						formLabel.innerHTML = configformulaire[key]["module"][panel]["elements"][element]["balise"];
						formText = document.createElement('input');
						formText.type = 'text';
						formText.value = '';
						if ((hasSave) && (panel in _saveFormulaire['formulaire-' + key]) && (_saveFormulaire['formulaire-' + key][panel] != "")) {
							if (_saveFormulaire['formulaire-' + key][panel][configformulaire[key]["module"][panel]["elements"][element]["name"]] != "") {
								formText.value = _saveFormulaire['formulaire-' + key][panel][configformulaire[key]["module"][panel]["elements"][element]["name"]];
							}
						}
						formPanel.appendChild(formLabel);
						formPanel.appendChild(formText);
					}
					break;
			}
			formVue.append(formNavIntent);
			formVue.appendChild(formPanel);
		}
		form.append(formVue);
		document.getElementById('pageIntent').appendChild(form);
	}

	function showForm(formId) {
		// Masquer tous les formulaires
		for (key in configformulaire) {
			document.getElementById(key).style.display = 'none';
		}
		// Afficher le formulaire spécifié
		document.getElementById(formId).style.display = 'block';
	}

	function showPanel(formId, panelId) {
		if (document.getElementById(formId + panelId).style.display == 'block') {
			document.getElementById(formId + panelId).style.display = 'none';
		} else {
			document.getElementById(formId + panelId).style.display = 'block';
		}
	}

</script>