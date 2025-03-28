(function () {
	const app = window.app;
	app.afterInit(function () {

		// Submit button
		const stateIndicator = new EventEmitter();
		const submitButton = document.getElementById('addProjectButton');
		if (submitButton) {
			stateIndicator.subscribe((state) => {
				submitButton.disabled = !state;
			});
		}

		// Project path input
		let pathError = false;
		const projectPathInput = document.getElementById('projectPath');
		const http = new HttpClient();
		if (projectPathInput) {
			const container = projectPathInput.parentElement;
			const infoMessage = container.querySelector('.infoMessage');
			const pathErrorMessage = container.querySelector('.errorMessage');
			projectPathInput.addEventListener('change', (event) => {
				if (event.target.value === '') {
					projectPathInput.classList.remove('error');
					infoMessage.style.display = 'none';
					pathErrorMessage.style.visibility = 'hidden';
					pathErrorMessage.style.display = 'block';
					stateIndicator.next(false);
					return;
				}
				if (!projectPathInput.checkValidity()) {
					infoMessage.style.display = 'none';
					projectPathInput.classList.add('error');
					pathErrorMessage.innerHTML = 'Invalid pattern!';
					pathErrorMessage.style.visibility = 'visible';
					stateIndicator.next(false);
					return;
				}
				http
					.get(
						'new-project/check-path',
						{
							projectPath: event.target.value
						}
					).subscribe({
						next: (response) => {
							if (response) {
								if (response.pathValid) {
									const gitIcon = document.createElement('img');
									gitIcon.src = 'Views/Assets/git.png';
									gitIcon.style.width = '16px';
									gitIcon.style.height = '16px';
									gitIcon.style.marginRight = '5px';
									infoMessage.innerHTML = 'This path is a valid git repository';
									infoMessage.prepend(gitIcon);
									pathErrorMessage.style.display = 'none';
									infoMessage.style.display = 'block';
									pathError = false;
									if (
										!nameError &&
										projectNameInput &&
										projectNameInput.value &&
										projectNameInput.checkValidity()
									) {
										stateIndicator.next(true);
									}
								} else {
									pathError = true;
									stateIndicator.next(false);
									pathErrorMessage.innerHTML = 'This path is not a valid git repository!';
									infoMessage.style.display = 'none';
									pathErrorMessage.style.display = 'block';
									pathErrorMessage.style.visibility = 'visible';
									projectPathInput.classList.add('error');
								}
							}
						}
					});
			});
			projectPathInput.addEventListener('keydown', (event) => {
				stateIndicator.next(false);
				projectPathInput.classList.remove('error');
				infoMessage.style.display = 'none';
				pathErrorMessage.style.visibility = 'hidden';
				pathErrorMessage.style.display = 'block';
			});
		}

		// Project name input
		let nameError = false;
		const projectNameInput = document.getElementById('projectName');
		const nameInputContainer = projectNameInput.parentElement;
		const nameErrorMessage = nameInputContainer.querySelector('.errorMessage');
		projectNameInput.addEventListener('change', (event) => {
			if (event.target.value === '') {
				stateIndicator.next(false);
				return;
			}
			if (!event.target.checkValidity()) {
				stateIndicator.next(false);
				projectNameInput.classList.add('error');
				nameErrorMessage.innerHTML = 'Invalid project name pattern!';
				nameErrorMessage.style.visibility = 'visible';
				return;
			}
			http.get(
				'new-project/check-name',
				{
					projectName: event.target.value
				}
			).subscribe({
				next: (response) => {
					if (response) {
						if (response.projectExists) {
							projectNameInput.classList.add('error');
							nameErrorMessage.innerHTML = 'This project name already exists!';
							nameErrorMessage.style.visibility = 'visible';
							stateIndicator.next(false);
						} else {
							if (
								!pathError &&
								projectPathInput &&
								projectPathInput.value &&
								projectPathInput.checkValidity()
							) {
								stateIndicator.next(true);
							}
						}
					}
				}
			});
		});
		projectNameInput.addEventListener('keydown', (event) => {
			stateIndicator.next(false);
			projectNameInput.classList.remove('error');
			nameErrorMessage.style.visibility = 'hidden';
		});

		// Add project button
		const btn = document.getElementById('addProjectButton');
		if (btn) {
			btn.addEventListener('click', (event) => {
				event.preventDefault();
				if (!projectPathInput || !projectNameInput) {
					console.error('Project path or name input not found');
					return;
				}
				const path = projectPathInput.value;
				const name = projectNameInput.value;
				http.post('new-project', {
					projectPath: path,
					projectName: name
				}).subscribe({
					next: (response) => {
						console.log(response);
						if (response && response.status) {
							if (
								response.status === 'success' &&
								response.newProjectName &&
								typeof response.newProjectName === 'string'
							) {
								console.log(response);
								window.location.href = 'projects/' . response.newProjectName;
							} else {
								if (response.field && response.message) {
									const errorField = document.getElementById(response.field);
									const infoMessage = errorField.parentElement.querySelector('.infoMessage');
									const errorMessage = errorField.parentElement.querySelector('.errorMessage');
									if (errorField) {
										if (infoMessage) {
											infoMessage.style.display = 'none';
											errorMessage.style.display = 'block';
										}
										errorField.classList.add('error');
										errorMessage.innerHTML = response.message;
										errorMessage.style.visibility = 'visible';
									}
								}
							}
						}
					}
				});
			});
		}
	});
})();