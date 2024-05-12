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

<div id="div_alertIntentsForm"></div>
<div style="display:flex;align-items: flex-start; height: 100%; overflow: hidden;">
	<div id="menu" style="display:flex;flex-direction: column; width: 20%; overflow-y: auto; height:100%;">
		<div id="indexIntent">
		</div>
		<a class="btn btn-danger btn-sm roundedRight" id="bt_resetform"><i class="fas fa-undo"></i> Réinitialiser</a>
		<a class="btn btn-success btn-sm roundedRight" id="bt_trainmodel"><i class="fas fa-check-circle"></i> Sauvegarder / Ré-entraîner</a>
	</div>
	<div id="pageIntent" style="display:flex; overflow-y: auto; height:100%; width: 80%">
	</div>
</div>


<script>
	console.log(configformulaire)
	console.log(_saveFormulaire)
	console.log(varList)
	for (key in configformulaire) {
		// ajout de l'intent dans l'index a	gauche
		let _menuIntent = document.createElement('div');
		_menuIntent.style.display = 'flex';
		_menuIntent.style.justifyContent = 'space-between';
		_menuIntent.innerHTML = '<a href="#" onclick="showForm(\'' + configformulaire[key]["idForm"] + '\')">' + configformulaire[key]["name"] + '</a>';
		hasSave = false;
		if (configformulaire[key]["idForm"] in _saveFormulaire) {
			hasSave = true;
		}
		if ((hasSave) && ('active' in _saveFormulaire['formulaire-' + configformulaire[key]["idForm"]])) {
			if ($_saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['active'] == "1") {
				_menuIntent.innerHTML += '<input type="checkbox" id="select' + configformulaire[key]["idForm"] + '" name="' + configformulaire[key]["name"] + '" checked>';
			} else {
				_menuIntent.innerHTML += '<input type="checkbox" id="select' + configformulaire[key]["idForm"] + '" name="' + configformulaire[key]["name"] + '">';
			}
		} else {
			_menuIntent.innerHTML += '<input type="checkbox" id="select' + configformulaire[key]["idForm"] + '" name="' + configformulaire[key]["name"] + '">';
		}
		document.getElementById('indexIntent').appendChild(_menuIntent);

		// ajout du formulaire
		form = document.createElement('div');
		form.id = configformulaire[key]["idForm"];
		form.style.display = 'none';
		for (configModule in configformulaire[key]["module"]) {
			formModule = document.createElement('div');
			formModule.id = configformulaire[key]["idForm"] + "_" + configformulaire[key]["module"][configModule]["name"]
			if (configformulaire[key]["module"][configModule]["show"]) {
				formModule.style.display = 'block';
			} else {
				formModule.style.display = 'none';
			}
			let _label = document.createElement('label');
			_label.innerHTML = configformulaire[key]["module"][configModule]["label"];
			formModule.appendChild(_label);
			switch (configformulaire[key]["module"][configModule]["typeHTMLForm"]) {
				case "select":
					let _select = document.createElement('select');
					if (configformulaire[key]["module"][configModule]["multiple"]) {
						_select.multiple = true;
					}
					let _optionContent = "";
					if (typeof configformulaire[key]["module"][configModule]["option"] === 'string' && configformulaire[key]["module"][configModule]["option"].startsWith("js")) {
						_optionContent = varList[configformulaire[key]["module"][configModule]["option"].slice(2)];
					} else {
						_optionContent = configformulaire[key]["module"][configModule]["option"];
					}
					for (let [keyOP, value] of Object.entries(_optionContent)) {
						// if defaultvalue exists and contains the key, then select the option
						let _option = document.createElement('option');
						_option.value = keyOP;
						_option.text = value;
						if (hasSave) {
							for (let i = 0; i < _saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'].length; i++) {
								if (_saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'][i]['name'] == configformulaire[key]["module"][configModule]["name"]) {
									for (let j = 0; j < _saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'][i]['value'].length; j++) {
										if (_saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'][i]['value'][j] == keyOP) {
											_option.selected = true;
										}
									}
								}
							}
						} else if (configformulaire[key]["module"][configModule]["defaultValue"] && configformulaire[key]["module"][configModule]["defaultValue"].includes(value)) {
							_option.selected = true;
						}
						_select.appendChild(_option);
					}
					_select.size = configformulaire[key]["module"][configModule]["size"];
					formModule.appendChild(_select);
				break;
				case "checkbox":
					let _checkbox = document.createElement('input');
					_checkbox.type = "checkbox";
					formModule.appendChild(_checkbox);
					if (hasSave) {
						for (let i = 0; i < _saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'].length; i++) {
							if (_saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'][i]['name'] == configformulaire[key]["module"][configModule]["name"]) {
								_checkbox.checked = _saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'][i]['value'];
							}
						}
					} else if (configformulaire[key]["module"][configModule]["defaultValue"]) {
						_checkbox.checked = true;
					}
				break;
				case "multi-texte":
					let _div = document.createElement('div');
					_div.style = "display: flex; flex-direction: column;";
					_div.id = configformulaire[key]["idForm"] + configformulaire[key]["module"][configModule]["name"] + "_multiTexte";
					if (hasSave) {
						for (let i = 0; i < _saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'].length; i++) {
							if (_saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'][i]['name'] == configformulaire[key]["module"][configModule]["name"]) {
								// cut the string with the separator ";"
								let _value = _saveFormulaire['formulaire-' + configformulaire[key]["idForm"]]['intents'][i]['value'].split(";");
								for (let j = 0; j < _value.length; j++) {
									let _multiTexte = document.createElement('input');
									_multiTexte.type = "text";
									_multiTexte.style = "margin-top: 5px;";
									_multiTexte.value = _value[j];
									_multiTexte.setAttribute("oninput", "toggleInput('" + _div.id + "',this);");
									_div.appendChild(_multiTexte);
								}
							}
						}
					} else if (configformulaire[key]["module"][configModule]["defaultValue"]) {
						for (let i = 0; i < configformulaire[key]["module"][configModule]["defaultValue"].length; i++) {
							let _multiTexte = document.createElement('input');
							_multiTexte.type = "text";
							_multiTexte.style = "margin-top: 5px;";
							_multiTexte.value = configformulaire[key]["module"][configModule]["defaultValue"][i];
							_multiTexte.setAttribute("oninput", "toggleInput('" + _div.id + "',this);");
							_div.appendChild(_multiTexte);
						}
					}
					let _multiTexte = document.createElement('input');
					_multiTexte.type = "text";
					_multiTexte.style = "margin-top: 5px;";
					_multiTexte.setAttribute("oninput", "toggleInput('" + _div.id + "',this);");
					_div.appendChild(_multiTexte);
					formModule.appendChild(_div);
				break;
			}
			form.appendChild(formModule);
		}
		document.getElementById('pageIntent').appendChild(form);
	}

	function showForm(formId) {
		// Masquer tous les formulaires
		for (key in configformulaire) {
			document.getElementById(configformulaire[key]["idForm"]).style.display = 'none';
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

    function toggleInput(container,input) {
        var inputsContainer = document.getElementById(container);
        if (input === inputsContainer.lastElementChild && input.value !== "") {
            var newInput = document.createElement("input");
            newInput.type = "text";
			newInput.style = "margin-top: 5px;";
            newInput.setAttribute("oninput", "toggleInput('" + container + "',this);");
            inputsContainer.appendChild(newInput);
        }
        else if (input !== inputsContainer.lastElementChild && input.value === "") {
            inputsContainer.removeChild(input);
        }
    }

	$('#bt_trainmodel').off().on('click',function() {
		saveIntentsForm()
	})

	function saveIntentsForm() {
		let _formulaire = {};
		for (key in configformulaire) {
			let _select = document.getElementById('select' + configformulaire[key]["idForm"]);
			if (_select.checked) {
				_formulaire[configformulaire[key]["idForm"]] = {
					"active": "1"
				}
			} else {
				_formulaire[configformulaire[key]["idForm"]] = {
					"active": "0"
				}
			}
			_formulaire[configformulaire[key]["idForm"]]['module'] = [];
			for (configModule in configformulaire[key]["module"]) {
				let _module = configformulaire[key]["module"][configModule];
				let _moduleValue = []
				switch (_module["typeHTMLForm"]) {
					case "select":
						let _select = document.getElementById(configformulaire[key]["idForm"] + "_" + _module["name"]).querySelector('select');
						if (_select.multiple) {
							_moduleValue = [];
							for (let i = 0; i < _select.options.length; i++) {
								if (_select.options[i].selected) {
									_moduleValue.push(_select.options[i].value);
								}
							}
						} else {
							_moduleValue = _select.options[_select.selectedIndex].value;
						}
					break;
					case "checkbox":
						let _checkbox = document.getElementById(configformulaire[key]["idForm"] + "_" + _module["name"]).querySelector('input');
						_moduleValue = _checkbox.checked;
					break;
					case "multi-texte":
						let _div = document.getElementById(configformulaire[key]["idForm"] + _module["name"] + "_multiTexte");
						_moduleValue = [];
						for (let i = 0; i < _div.children.length; i++) {
							if (_div.children[i].value != "") {
								_moduleValue.push(_div.children[i].value);
							}
						}
					break;
				}
				console.log("10")
				_formulaire[configformulaire[key]["idForm"]]['module'].push({
					"name": _module["name"],
					"value": _moduleValue,
					"class" : _module["class"],
					"rhasspyslotlink" : _module["rhasspyslotlink"] ? _module["rhasspyslotlink"] : ""
				})
			}
			_formulaire[configformulaire[key]["idForm"]]['phrase'] = configformulaire[key]["phrase"];
		}
		// debug
		console.log(_formulaire)
		$.ajax({
			type: "POST",
			url: "plugins/jeerhasspy/core/ajax/jeerhasspy.ajax.php",
			data: {
			action: "buildmodel",
			intents: json_encode(_formulaire)
			},
			dataType: 'json',
			error: function (request, status, error) {
				$('#div_alertIntentsForm').showAlert({message: error.message, level: 'danger'})
			},
			success: function (data) {
			if (data.state != 'ok') {
				$('#div_alertIntentsForm').showAlert({message: error.message, level: 'danger'})
				return
			}
			$('#div_alertIntentsForm').showAlert({message: '{{Sauvegarde effectuée}}', level: 'success'});
			},
		})
	}
</script>