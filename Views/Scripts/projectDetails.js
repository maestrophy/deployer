window.app.afterInit(function () {
	const branchSelector = document.querySelector('#branchSelector');
	const deployBtn = document.querySelector('#deployBtn');
	if (branchSelector && deployBtn) {
		branchSelector.addEventListener('change', function (event) {
			if (event.target.value === currentlyActiveBranch) {
				deployBtn.innerHTML = 'Update';
			} else {
				deployBtn.innerHTML = 'Deploy';
			}
		});
	}
});