window.app.afterInit(function () {
	const projectName = window.location.pathname.split('/')[2];
	const branchSelector = document.querySelector('#branchSelector');
	const branchSelectorErrorMessage = branchSelector.parentElement.querySelector('.errorMessage');
	const deployBtn = document.querySelector('#deployBtn');
	
	if (
		!projectName ||
		!branchSelector ||
		!branchSelectorErrorMessage ||
		!deployBtn
	) {
		console.warn('Some required html elements are not present. Some unexpected behavior can appear!');
	}
	if (branchSelector && deployBtn) {
		branchSelector.addEventListener('change', function (event) {
			branchSelector.classList.remove('error');
			branchSelectorErrorMessage.style.visibility = 'hidden';
			branchSelectorErrorMessage.innerHTML = 'Error placeholder';
			if (event.target.value === currentlyActiveBranch) {
				deployBtn.innerHTML = 'Update';
			} else {
				deployBtn.innerHTML = 'Deploy';
			}
		});
	}

	deployBtn.addEventListener('click', function (event) {
		if (branchSelector.value) {
			event.preventDefault();
			const http = new HttpClient();
			http.patch(
				'/projects/' + projectName + '/deploy',
				{
					branch: branchSelector.value
				}
			).subscribe({
				onProgress: (partial) => {
					if (partial.allScripts) {
						showProgress(partial.allScripts);
					}
					if (partial.scriptFinished || partial.scriptFailed) {
						updateStatus(partial);
					}
				}
			});
		} else {
			branchSelector.classList.add('error');
			branchSelectorErrorMessage.innerHTML = 'There is no selected value!';
			branchSelectorErrorMessage.style.visibility = 'visible';
		}
	});

	function showProgress(scripts) {
		const scriptsScreen = document.querySelector('#processScreen');
		const scriptEntries = scripts.map(script => createProcessEntry(script));
		scriptEntries.forEach(scriptEntry => scriptsScreen.appendChild(scriptEntry));
		scriptsScreen.style.display = 'inline-flex';
		updateStatus();
	}

	function createProcessEntry(script) {
		const scriptEntry = document.createElement('div');
		scriptEntry.classList.add('scriptEntry');
		const statusSymbol = document.createElement('img');
		statusSymbol.src = '/Views/Assets/waiting.svg';
		statusSymbol.classList.add('statusSymbol');
		const statusLiteral = document.createElement('div');
		statusLiteral.classList.add('statusLiteral', 'ilf');
		statusLiteral.innerHTML = 'Waiting...';
		const scriptLiteral = document.createElement('div');
		scriptLiteral.classList.add('scriptLiteral', 'ilf', 'no-scrollbar');
		scriptLiteral.innerHTML = script.command;
		scriptEntry.appendChild(statusSymbol);
		scriptEntry.appendChild(statusLiteral);
		scriptEntry.appendChild(scriptLiteral);
		return scriptEntry;
	}

	function updateStatus(statusMessage = {}) {
		const scriptScreen = document.querySelector('#processScreen');
		const scriptEntries = Array.from(scriptScreen.querySelectorAll('div.scriptEntry'));
		console.log(scriptEntries);
		console.log(statusMessage);
		const currentlyRunningScriptIndex = scriptEntries.findIndex(scriptEntry => scriptEntry.querySelector('img').src.endsWith('/Views/Assets/miniSpinner.svg'));
		console.log(currentlyRunningScriptIndex);
		if (currentlyRunningScriptIndex < 0) {
			scriptEntries[0].querySelector('img').src = '/Views/Assets/miniSpinner.svg';
			return;
		}
		if (statusMessage.scriptFinished) {
			scriptEntries[currentlyRunningScriptIndex].querySelector('img').src = '/Views/Assets/success.svg';
			scriptEntries[currentlyRunningScriptIndex].querySelector('div.statusLiteral').innerHTML = 'Successfully executed';
			if (currentlyRunningScriptIndex === scriptEntries.length - 1) {
				indicateFullSuccess();
				return;
			}
			scriptEntries[currentlyRunningScriptIndex + 1].querySelector('img').src = '/Views/Assets/miniSpinner.svg';
			scriptEntries[currentlyRunningScriptIndex + 1].querySelector('div.statusLiteral').innerHTML = 'Executing...';
		} else if (statusMessage.scriptFailed) {
			for (let i = currentlyRunningScriptIndex; i < scriptEntries.length; i++) {
				scriptEntries[i].querySelector('img').src = '/Views/Assets/fail.svg';
				scriptEntries[i].querySelector('div.statusLiteral').innerHTML = 'Execution failed';
			}
			indicateError(statusMessage.errorMessage);
		}
	}

	function indicateFullSuccess() {
		console.log('All scripts executed successfully');
	}

	function indicateError(errorMessage) {
		console.log('Error: ' + errorMessage);
	}
});