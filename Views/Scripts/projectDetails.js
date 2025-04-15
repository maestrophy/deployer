window.app.afterInit(function () {
	const tabFlaps = Array.from(document.querySelectorAll('span.tab-flap'));
	tabFlaps.forEach(flap => flap.addEventListener('click', event => activateTab(event.target.id)));
	const tabs = Array.from(document.querySelectorAll('div.tab'));

	if (!(
		tabFlaps &&
		tabFlaps.length &&
		tabs &&
		tabs.length
	)) {
		return;
	}

	const queryParams = Object.fromEntries(
		Array.from(
			(new URLSearchParams(window.location.search)).entries()
		)
	);

	const activeTab = queryParams.activeTab;
	if (
		!activeTab ||
		!/^\d{1,}$/.test(activeTab) ||
		Number(activeTab) > tabs.length
	) {
		activateTab(1);
	} else {
		activateTab(Number(activeTab));
	}
});

function activateTab(id) {
	const tabFlaps = Array.from(document.querySelectorAll('span.tab-flap'));
	tabFlaps.forEach(flap => {
		if (flap.id === id) {
			flap.classList.add('active');
		} else {
			flap.classList.remove('active');
		}
	});
	const tabs = Array.from(document.querySelectorAll('div.tab'));
	for (x = 0; x < tabs.length; x++) {
		if (x + 1 === Number(id)) {
			tabs[x].classList.add('active');
		} else {
			tabs[x].classList.remove('active');
		}
	}
	const url = new URL(window.location);
	url.searchParams.set('activeTab', id);
	window.history.replaceState({}, '', url);
}