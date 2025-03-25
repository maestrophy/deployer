const app = {
	afterInit: function(callback) {
		document.addEventListener('DOMContentLoaded', callback);
	}
};
window.app = app;