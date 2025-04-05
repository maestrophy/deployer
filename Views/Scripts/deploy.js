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
				'projects/' + projectName + '/deploy',
				{
					branch: branchSelector.value
				}
			).subscribe({
				onProgress: (partial) => {
					if (partial.allScripts) {
						showProgress(partial.allScripts);
					}
					if (partial.scriptFinished) {
						markScriptAsFinished(partial.scriptFinished);
					}
					if (partial.scriptFailed) {
						errorOnExecutingScript(partial.scriptFailed, partial.errorMessage)
					}
				},
				next: (response) => {}
			});
		} else {
			branchSelector.classList.add('error');
			branchSelectorErrorMessage.innerHTML = 'There is no selected value!';
			branchSelectorErrorMessage.style.visibility = 'visible';
		}
	});

	function showProgress(scripts) {}

	function markScriptAsFinished(scriptName) {}

	function errorOnExecutingScript(scriptName, message) {}
});