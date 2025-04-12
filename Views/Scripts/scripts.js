window.app.afterInit(function () {
	const addScriptBtn = document.querySelector('button#scriptPush');
	addScriptBtn.addEventListener('click', function (event) {
		event.preventDefault();
		const scriptListContainer = document.querySelector('div.scriptsContainer');
		if (scriptListContainer) {
			addNewScript(scriptListContainer);
		}
	});
	const upperButtons = document.querySelectorAll('div.positioners span.btn-container button.upper');
	upperButtons.addEventListener('click', );
	const lowerButtons = document.querySelectorAll('div.positioners span.btn-container button.lower');
	lowerButtons.addEventListener('click', );
	const editCommandButtons = document.querySelectorAll('div.actionButtonsContainer button.editCommandBtn');
	editCommandButtons.addEventListener('click', editScript);
	const editPathButtons = document.querySelectorAll('div.actionButtonsContainer button.editPathBtn');
	editPathButtons.addEventListener('click', editPath);
	const removeButtons = document.querySelectorAll('div.actionButtonsContainer button.removeBtn');
	removeButtons.addEventListener('click', removeFromList);
});

function addNewScript(container) {

	// Creating elements
	const newScriptDiv = document.createElement('div');
	const positionerDiv = document.createElement('div');
	const upperBtnContainer = document.createElement('span');
	const upperBtn = document.createElement('button');
	const lowerBtnContainer = document.createElement('span');
	const lowerBtn = document.createElement('button');
	const upperArrow = document.createElement('img');
	const lowerArrow = document.createElement('img');
	const literalContainer = document.createElement('div');
	const commandInput = document.createElement('input');
	const targetPath = document.createElement('p');
	const actionButtonsContainer = document.createElement('div');
	const editCommandBtn = document.createElement('button');
	const editCommandSymbol = document.createElement('img');
	const editPathBtn = document.createElement('button');
	const editPathSymbol = document.createElement('img');
	const removeBtn = document.createElement('button');
	const binSymbol = document.createElement('img');

	// Adding classes and attributes
	newScriptDiv.classList.add('scriptHolder');
	positionerDiv.classList.add('positioners');
	upperBtnContainer.classList.add('ilb', 'btn-container');
	upperBtn.classList.add('upper');
	upperBtn.disabled = true;
	upperArrow.src = '/Views/Assets/arrowUp.svg';
	lowerBtnContainer.classList.add('ilb', 'btn-container');
	lowerBtn.classList.add('lower');
	lowerBtn.disabled = true;
	lowerArrow.src = '/Views/Assets/arrowDown.svg';
	literalContainer.classList.add('literalContainer');
	commandInput.classList.add('commandInput', 'translucentInput');
	commandInput.type = 'text';
	commandInput.id = 'commandInput';
	commandInput.name = 'commandInput';
	commandInput.addEventListener('blur', onCommandInputBlur);
	targetPath.classList.add('targetPath');
	targetPath.innerHTML = currentProjectPath ?? '/';
	actionButtonsContainer.classList.add('actionButtonsContainer');
	editCommandBtn.classList.add('editCommandBtn');
	editCommandBtn.disabled = true;
	editCommandSymbol.src = "/Views/Assets/command.svg";
	editPathBtn.classList.add('editPathBtn');
	editPathBtn.disabled = true;
	editPathSymbol.src = "/Views/Assets/openedBlotter.svg";
	removeBtn.classList.add('removeBtn');
	removeBtn.disabled = true;
	binSymbol.src = "/Views/Assets/trashBin.svg";

	// Build
	upperBtn.appendChild(upperArrow);
	lowerBtn.appendChild(lowerArrow);

	upperBtnContainer.appendChild(upperBtn);
	lowerBtnContainer.appendChild(lowerBtn);

	positionerDiv.appendChild(upperBtnContainer);
	positionerDiv.appendChild(lowerBtnContainer);

	literalContainer.appendChild(commandInput);
	literalContainer.appendChild(targetPath);

	editCommandBtn.appendChild(editCommandSymbol);
	editPathBtn.appendChild(editPathSymbol);
	removeBtn.appendChild(binSymbol);

	actionButtonsContainer.appendChild(editCommandBtn);
	actionButtonsContainer.appendChild(editPathBtn);
	actionButtonsContainer.appendChild(removeBtn);

	newScriptDiv.appendChild(positionerDiv);
	newScriptDiv.appendChild(literalContainer);
	newScriptDiv.appendChild(actionButtonsContainer);

	container.appendChild(newScriptDiv);
	commandInput.focus();
}

function getScripts() {
	const scriptsContainer = document.querySelector('div.scriptsContainer');
	if (!scriptsContainer) {
		console.error('Script container not found!');
		return;
	} 
	const scriptEntries = Array.from(scriptsContainer.querySelectorAll('div.scriptHolder'));
	if (scriptEntries && scriptEntries.length) {
		let scripts = [];
		let schedule = 'beforePull';
		scriptEntries.forEach(scriptEntry => {
			if (scriptEntry.classList.contains('checkout')) {
				schedule = 'afterPull';
				return;
			}
			let command = scriptEntry.querySelector('p.commandLiteral')?.innerHTML?.trim();
			let targetPath = scriptEntry.querySelector('p.targetPath')?.innerHTML?.trim();
			script = {
				command,
				schedule,
				targetPath
			};
			scripts.push(script);
		});
	} else {
		return [];
	}
}

function editScript(event) {
	let buttonContainer = event.target.parentElement;
	let literalContainer = buttonContainer.previousElementSibling;
	if (literalContainer && literalContainer.classList.contains('literalContainer')) {
		setButtonsDisabled(literalContainer, true);
		let commandContainer = [...literalContainer.children].find(el => el.classList.contains('commandLiteral'));
		if (!commandContainer) {
			console.error('Command paragraph not found!');
			return;
		}
		let currentCommandValue = commandContainer.innerHTML.trim();
		let commandInput = document.createElement('input');
		commandInput.type = 'text';
		commandInput.classList.add('commandInput', 'translucentInput');
		commandInput.value = currentCommandValue;
		commandInput.addEventListener('blur', onCommandInputBlur);
		commandContainer.remove();
		literalContainer.prepend(commandInput);
		commandInput.focus();
	} else {
		console.error('Element with class literalContainer not found as button container sibling!');
	}
}

function onCommandInputBlur(event) {
	let commandInput = event.target;
	let currentCommand = commandInput.value;
	if (!currentCommand) {
		removeFromList(commandInput);
		return;
	}
	let literalContainer = commandInput.parentElement;
	if (!literalContainer) {
		return;
	}
	setButtonsDisabled(literalContainer, false);
	if (!literalContainer.querySelector('p.commandLiteral')) {
		let commandDisplay = document.createElement('p');
		commandDisplay.classList.add('commandLiteral');
		commandDisplay.innerHTML = currentCommand;
		commandInput.remove();
		literalContainer.prepend(commandDisplay);
	} else {
		console.warn('Command editing failed, command paragraph was already present with input!');
	}
}

function editPath(event) {
	let buttonContainer = event.target.parentElement;
	let literalContainer = buttonContainer.previousElementSibling;
	if (literalContainer && literalContainer.classList.contains('literalContainer')) {
		setButtonsDisabled(literalContainer, true);
		let pathContainer = [...literalContainer.children].find(el => el.classList.contains('targetPath'));
		if (!pathContainer) {
			console.error('Target path paragraph not found!');
			return;
		}
		let currentPathValue = pathContainer.innerHTML.trim();
		let targetPathInput = document.createElement('input');
		targetPathInput.type = 'text';
		targetPathInput.classList.add('pathInput', 'translucentInput');
		targetPathInput.value = currentPathValue;
		targetPathInput.addEventListener('blur', onPathInputBlur);
		pathContainer.remove();
		literalContainer.appendChild(targetPathInput);
		targetPathInput.focus();
	} else {
		console.error('Element with class literalContainer not found as button container sibling!');
	}
}

function onPathInputBlur(event) {
	let targetPathInput = event.target;
	let currentPathValue = targetPathInput.value;
	if (!currentPathValue) {
		currentPathValue = '/';
	}
	let literalContainer = targetPathInput.parentElement;
	if (!literalContainer) {
		return;
	}
	setButtonsDisabled(literalContainer, false);
	if (!literalContainer.querySelector('p.targetPath')) {
		let targetPathDisplay = document.createElement('p');
		targetPathDisplay.classList.add('targetPath');
		targetPathDisplay.innerHTML = currentPathValue;
		targetPathInput.remove();
		literalContainer.appendChild(targetPathDisplay);
	} else {
		console.warn('Target path editing failed, target paragraph was already present with input!');
	}
}

function setButtonsDisabled(literalContainer, lever) {
	let scriptLine = literalContainer.parentElement;
	if (!scriptLine) {
		return;
	}
	let buttons = Array.from(scriptLine.querySelectorAll('button'));
	if (buttons && buttons.length) {
		buttons.forEach(btn => btn.disabled = lever);
	}
	let scriptAddBtn = document.querySelector('button#scriptPush');
	if (scriptAddBtn) {
		scriptAddBtn.disabled = lever;
	}
}

function removeFromList(element) {
	let scriptRow, parentElement;
	if (
		element.classList.contains('scriptHolder') &&
		element.tagName === 'div'
	) {
		scriptRow = element;
	} else {
		scriptRow = element.closest('div.scriptHolder')
	}
	if (!scriptRow) {
		return;
	}
	scriptRow.remove();
}

function moveScriptUpper(event) {
	const moveButton = event.target;
	const scriptRecord = moveButton.closest('div.scriptHolder');
	const scriptRecordAbove = scriptRecord.previousElementSibling;
	const listContainer = scriptRecord.parentElement;

	if (!scriptRecordAbove) {
		return;
	}
	if (scriptRecordAbove.tagName !== 'div') {
		console.error('The script container is not the correct html element!');
		return;
	}
	if (!scriptRecordAbove.classList.contains('scriptHolder')) {
		console.error('The script container is not the correct html element!');
		return;
	}
	if (!listContainer) {
		console.error('There is no list container!');
		return;
	}
	listContainer.insertBefore(scriptRecord, scriptRecordAbove);
}

function moveScriptLower(event) {
	const moveButton = event.target;
	const scriptRecord = moveButton.closest('div.scriptHolder');
	const scriptRecordBelow = scriptRecord.nextElementSibling;
	const listContainer = scriptRecord.parentElement;

	if (!scriptRecordBelow) {
		return;
	}
	if (scriptRecordBelow.tagName !== 'div') {
		console.error('The script container is not the correct html element!');
		return;
	}
	if (!scriptRecordBelow.classList.contains('scriptHolder')) {
		console.error('The script container is not the correct html element!');
		return;
	}
	if (!listContainer) {
		console.error('There is no list container!');
		return;
	}
	listContainer.insertAfter(scriptRecord, scriptRecordBelow);
}

function enableDisableMoveButtonsByRecordPosition(element) {
	if (element.tagName !== 'div' || !element.classList.contains('scriptHolder')) {
		const scriptRecord = element.closest('div.scriptHolder');
	} else {
		const scriptRecord = element;
	}

	const upperBtn = scriptRecord.querySelector('button.upper');
	const lowerBtn = scriptRecord.querySelector('button.lower');

	if (!scriptRecord.nextElementSibling) {
		lowerBtn.disabled = true;
	} else {
		lowerBtn.disabled = false;
	}

	if (!scriptRecord.previousElementSibling) {
		upperBtn.disabled = true;
	} else {
		upperBtn.disabled = false;
	}
}