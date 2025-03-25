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
			const errorMessage = container.querySelector('.errorMessage');
			projectPathInput.addEventListener('change', (event) => {
				if (event.target.value === '') {
					projectPathInput.classList.remove('error');
					infoMessage.style.display = 'none';
					errorMessage.style.visibility = 'hidden';
					errorMessage.style.display = 'block';
					stateIndicator.next(false);
					return;
				}
				if (!projectPathInput.checkValidity()) {
					projectPathInput.classList.add('error');
					errorMessage.innerHTML = 'Invalid pattern!';
					errorMessage.style.visibility = 'visible';
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
									errorMessage.style.display = 'none';
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
									errorMessage.innerHTML = 'This path is not a valid git repository!';
									infoMessage.style.display = 'none';
									errorMessage.style.display = 'block';
									errorMessage.style.visibility = 'visible';
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
				errorMessage.style.visibility = 'hidden';
				errorMessage.style.display = 'block';
			});
		}

		// Project name input
		let nameError = false;
		const projectNameInput = document.getElementById('projectName');
		const nameInputContainer = projectNameInput.parentElement;
		const errorMessage = nameInputContainer.querySelector('.errorMessage');
		projectNameInput.addEventListener('change', (event) => {
			if (event.target.value === '') {
				stateIndicator.next(false);
				return;
			}
			http.get(
				'new-project/check-name',
				{
					projectName: event.target.value
				}
			).subscribe({
				next: (response) => {
					console.log(response);
					if (response) {
						if (response.projectExists) {
							projectNameInput.classList.add('error');
							errorMessage.innerHTML = 'This project name already exists!';
							errorMessage.visibility = 'visible';
							stateIndicator.next(false);
						} else {
							console.log(!pathError);
							console.log(projectPathInput);
							console.log(projectPathInput.value);
							console.log(projectPathInput.checkValidity());
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
			errorMessage.style.visibility = 'hidden';
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
						if (response && response.status) {
							if (response.status === 'success') {
								//
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
										console.log(response.message);
										errorField.classList.add('error');
										errorMessage.innerHTML = response.message;
										console.log(errorMessage);
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