

function EventEmitter () {
	this.subscribers = [];
}

EventEmitter.prototype = {

	subscribers: null,

	next: function (data = null) {
		this.event = data;
		this.subscribers.forEach((subscriber) => {
			try {
				subscriber(this.event);
			} catch (e) {
				console.error('Subscriber ' + subscriber + ' can\'t be fired!');
				console.error(e);
			}
		});
	},

	subscribe: function (subscriber) {
		if (typeof subscriber !== 'function') {
			throw new Error('Subscriber must be a function, ' + typeof subscriber + ' given!');
		}
		this.subscribers.push(subscriber);
	}
};

function BehaviorSubject (value = null) {
	this.subscribers = [];
	this.value = value;
}

BehaviorSubject.prototype = {

	value: null,

	subscribers: null,

	next: function (data = null) {
		this.value = data;
		this.subscribers.forEach((subscriber) => {
			try {
				subscriber(this.value);
			} catch (e) {
				console.error('Subscriber ' + subscriber + ' can\'t be fired!');
			}
		});
	},

	subscribe: function (subscriber) {
		if (typeof subscriber !== 'function') {
			throw new Error('Subscriber must be a function, ' + typeof subscriber + ' given!');
		}
		subscriber(this.value);
		this.subscribers.push(subscriber);
	},

	getValue: function () {
		return this.value;
	}
};

function Subject () {
	this.subscribers = [];
}

Subject.prototype = {

	value: null,

	subscribers: null,

	next: function (data = null) {
		this.value = data;
		this.subscribers.forEach((subscriber) => {
			try {
				subscriber(this.value);
			} catch (e) {
				console.error('Subscriber ' + subscriber + ' can\'t be fired!');
			}
		});
	},

	subscribe: function (subscriber) {
		if (typeof subscriber !== 'function') {
			throw new Error('Subscriber must be a function, ' + typeof subscriber + ' given!');
		}
		this.subscribers.push(subscriber);
	},

	getValue: function () {
		return this.value;
	}
};

function Observable () {
	this.nextSubscribers = [];
	this.onProgressSubscribers = [];
	this.errorSubscribers = [];
	this.completeSubscribers = [];
	this.subscribedNotifier = new EventEmitter();
};

Observable.prototype = {

	nextSubscribers: null,
	onProgressSubscribers: null,
	errorSubscribers: null,
	completeSubscribers: null,
	subscribedNotifier: null,

	subscribe: function (subscriber = {}) {
		if (typeof subscriber !== 'object') {
			throw new Error('Subscriber must be an object, ' + typeof subscriber + ' given!');
		}
		if (Array.isArray(subscriber)) {
			throw new Error('Subscriber must be an object, array given!');
		}
		subscriberTypes = ['next', 'error', 'complete'];
		if (subscriber.next && typeof subscriber.next === 'function') {
			this.nextSubscribers.push(subscriber.next);
		}
		if (subscriber.error && typeof subscriber.error === 'function') {
			this.errorSubscribers.push(subscriber.error);
		}
		if (subscriber.complete && typeof subscriber.complete === 'function') {
			this.completeSubscribers.push(subscriber.complete);
		}
		if (subscriber.onProgress && typeof subscriber.onProgress === 'function') {
			this.onProgressSubscribers.push(subscriber.onProgress);
		}
		this.subscribedNotifier.next();
	},

	next: function (data = null) {
		if (this.nextSubscribers.length) {
			this.nextSubscribers.forEach((subscriber) => subscriber(data));
		}
	},
	onProgress: function (data = null) {
		if (this.onProgressSubscribers.length) {
			this.onProgressSubscribers.forEach((subscriber) => subscriber(data));
		}
	},

	error: function (data = null) {
		if (this.errorSubscribers.length) {
			this.errorSubscribers.forEach((subscriber) => subscriber(data));
		}
	},

	complete: function () {
		if (this.completeSubscribers.length) {
			this.completeSubscribers.forEach((subscriber) => subscriber());
		}
		this.request = null;
	}
};

function HttpClient() {
	this.async = true;
};

HttpClient.prototype = {

	async: null,
	
	request: function (url, method, data = null, options = null) {
		url = this.validateUrl(url);
		method = this.validateMethod(method);
		data = this.validateData(data);
		options = this.validateOptions(options);
		headers = this.validateHeaders(options);

		headers['Content-Type'] = this.getContentTypeHeader(data);
		let result = new Observable();
		result.subscribedNotifier.subscribe(() => this.execute(headers, method, url, result, data));
		return result;
	},
	
	get: function (url, queryParams = null, headers = null) {
		return this.request(this.formatUrl(url, queryParams), 'GET', headers);
	},
	
	post: function (url, data = null, headers = null) {
		return this.request(url, 'POST', data, headers);
	},
	
	put: function (url, data = null, headers = null) {
		return this.request(url, 'PUT', data, headers);
	},
	
	patch: function (url, data = null, headers = null) {
		return this.request(url, 'PATCH', data, headers);
	},
	
	delete: function (url, data = null, headers = null) {
		return this.request(url, 'DELETE', data, headers);
	},
	
	head: function (url, data = null, headers = null) {
		return this.request(url, 'HEAD', data, headers);
	},

	getContentTypeHeader: function (data) {
		switch (true) {
			case !data:
				return 'application/json';
			case (
				typeof data === 'object' &&
				data.constructor &&
				data.constructor.name &&
				data.constructor.name === 'FormData'
			):
				return 'multipart/form-data';
			case (
				typeof data === 'object' &&
				data.constructor &&
				data.constructor.name &&
				data.constructor.name === 'URLSearchParams'
			):
				return 'application/x-www-form-urlencoded; charset=UTF-8';
			case typeof data === 'object':
				return 'application/json';
			case typeof data === 'string':
				return 'text/plain';
			default:
				return 'application/json';
		}
	},

	formatUrl: function (url, queryParams = null) {
		if (!typeof url === 'string') {
			throw new Error('Http request url must be string, ' + typeof url + ' given!');
		}
		if (url.length < 3) {
			throw new Error('Too short url!');
		}
		let queryStringRegex = /^\??((%[0-9A-Fa-f]{2})|[a-zA-Z0-9\-_\.~])+=((%[0-9A-Fa-f]{2})|[a-zA-Z0-9\-_\.~])+((&((%[0-9A-Fa-f]{2})|[a-zA-Z0-9\-_\.~])+=((%[0-9A-Fa-f]{2})|[a-zA-Z0-9\-_\.~])+)){0,}$/;
		let rawQueryStringRegex = /^\??[^=&]+=[^=&]+((&[^=&]+=[^=&]+)){0,}$/;
		if (queryParams && !['string', 'object'].includes(typeof queryParams)) {
			console.warn('Query params can be only object, or string, ' + typeof queryParams + ' given. Query parameters could not be set.');
		}
		let queryString = '';
		if (queryParams) {
			if (typeof queryParams === 'string') {
				if (queryStringRegex.test(queryParams)) {
					if (/^\?/.test(queryParams)) {
						queryString = queryParams.substring(1);
					} else {
						queryString = queryParams;
					}
				} else if (rawQueryStringRegex.test(queryParams)) {
					queryString = queryParams
						.split('&')
						.map(param =>
							param
								.split('=')
								.map(keyOrValue => encodeURI(keyOrValue))
								.join('=')
						)
						.join('&');
				} else {
					throw new Error('Wrong format of query parameters: ' + queryParams);
				}
			} else {
				queryString = Object.entries(queryParams)
					.map(([key, value]) => encodeURI(key) + '=' + encodeURI(value))
					.join('&');
			}
		}
		let rawUrl;
		if (url.includes('?')) {
			rawUrl = url.split('?')[0];
			urlParams = new URLSearchParams(url.split('?')[1]);
			if (urlParams.toString().length) {
				queryString = urlParams.toString() + (queryString.length ? '&' + queryString : '');
			}
		} else {
			rawUrl = url;
		}
		
		return rawUrl + (queryString.length ? '?' + queryString : '');
	},

	getHttpRequestMethods: function () {
		return [
			'GET',
			'POST',
			'PUT',
			'PATCH',
			'DELETE',
			'HEAD',
			'OPTIONS'
		];
	},

	validateUrl: function(url) {
		if (!typeof url === 'string') {
			throw new Error('Http request url must be string, ' + typeof url + ' given!');
		}
		return url;
	},

	validateMethod: function(method) {
		if (typeof method !== 'string') {
			throw new Error('Http request method must be a string!');
		}
		method = method.toUpperCase();
		if (!this.getHttpRequestMethods().includes(method)) {
			throw new Error('Method must be a valid HTTP request method, ' + method + ' given. The method stayed GET!');
		}
		return method;
	},

	validateData: function(data) {
		let requestData = null;
		if (data) {
			if (typeof data === 'function') {
				let rawData = data();
				if (typeof rawData !== 'object') {
					throw new Error('Data creator function must return an object, ' + typeof rawData + ' given!');
				}
				requestData = JSON.stringify(rawData);
			} else if (typeof data === 'object') {
				requestData = JSON.stringify(data);
			} else {
				throw new Error('Data must be an object, or a function, that returns an object, ' + typeof data + ' given!');
			}
		}
		return requestData;
	},

	validateHeaders: function(options) {
		let requestHeaders = {};
		if (options.headers) {
			if (typeof options.headers === 'function') {
				let rawHeaders = headers();
				if (typeof rawHeaders !== 'object') {
					throw new Error('Headers creator function must return an object, ' + typeof rawHeaders + ' given!');
				}
				requestHeaders = rawHeaders;
			} else if (typeof options.headers === 'object') {
				requestHeaders = options.headers;
			} else {
				throw new Error('Headers must be an object, or a function, that returns an object, ' + typeof options.headers + ' given!');
			}
		}
		return requestHeaders;
	},

	validateOptions: function(options) {
		if (!options) {
			return {};
		}
		if (typeof options === 'function') {
			let options = options();
			if (typeof options === 'object' && !Array.isArray('object')) {
				return options;
			} else {
				throw new Error('Options can be a function, but must return an object, ' + (Array.isArray(options) ? 'array' : typeof options) + ' given!');
			}
		} else if (typeof options === 'object') {
			return options;
		} else {
			throw new Error('Options must be an object, or a function, that returns an object, ' + typeof options + ' given!');
		}
	},

	execute: function(headers, method, url, observable, data) {
		let xhttp = new XMLHttpRequest();
		xhttp.open(method, url, this.async);
		Object.entries(headers).forEach(([key, value]) => {
			xhttp.setRequestHeader(key, value);
		});
		let responseLength = 0;
		xhttp.onreadystatechange = function () {
			let currentResponse = xhttp.responseText.substring(responseLength);
			if (xhttp.readyState === 3 && currentResponse.length) {
				if (
					xhttp.getResponseHeader('Content-Type').includes('application/json') ||
					xhttp.getResponseHeader('Content-Type').includes('text/json')
				) {
					try {
						response = JSON.parse(currentResponse);
					} catch (e) {
						console.error('On progress response is not valid JSON! ', currentResponse);
						return;
					}
				} else {
					response = currentResponse;
				}
				if (response.duringProgress) {
					responseLength = xhttp.responseText.length;
					observable.onProgress(response);
				}
			} else if (xhttp.readyState === 4) {
				// currentResponse = xhttp.responseText.substring(responseLengths[responseLengths.length - 3]);
				if (currentResponse) {
					if (
						xhttp.getResponseHeader('Content-Type').includes('application/json') ||
						xhttp.getResponseHeader('Content-Type').includes('text/json')
					) {
						try {
							response = JSON.parse(currentResponse);
						} catch (e) {
							observable.error({
								errorMessage: 'Response is not valid JSON!',
								status: xhttp.status,
								statusText: xhttp.statusText
							});
							return;
						}
					} else {
						response = currentResponse;
					}
					if (xhttp.status < 400) {
						observable.next(response);
					} else {
						observable.error({
							responseBody: response,
							status: xhttp.status,
							statusText: xhttp.statusText
						});
					}
				} else {
					if (xhttp.status < 400) {
						observable.next(null);
					} else {
						observable.error({
							responseBody: response,
							status: xhttp.status,
							statusText: xhttp.statusText
						});
					}
				}
				console.log(xhttp.responseText);
				observable.complete();
			}
		};
		/* xhttp.onload = () => {
			let response = null;
			if (xhttp.responseText) {
				if (
					xhttp.getResponseHeader('Content-Type').includes('application/json') ||
					xhttp.getResponseHeader('Content-Type').includes('text/json')
				) {
					try {
						response = JSON.parse(xhttp.responseText);
					} catch (e) {
						observable.error({
							errorMessage: 'Response is not valid JSON!',
							status: xhttp.status,
							statusText: xhttp.statusText
						});
						return;
					}
				} else {
					response = xhttp.responseText;
				}
			}
			if (xhttp.status < 400) {
				observable.next(response);
			} else {
				observable.error({
					responseBody: response,
					status: xhttp.status,
					statusText: xhttp.statusText
				});
			}
			observable.complete();
		} */
		switch (true) {
			case !data:
				xhttp.send();
				break;
			case typeof data === 'object' && data instanceof FormData:
				xhttp.send(data);
				break;
			case typeof data === 'object':
				xhttp.send(JSON.stringify(data));
				break;
			default:
				xhttp.send(data);
		}
	}
};

function AngularModal(html = '', id) {
	if (html) {
		this.html = html;
	}
	this.id = id;
	if (!document.angularModals) {
		document.angularModals = {};
	}
	document.angularModals[this.id] = this;
	this.input = new Observable();
	this.submitFormEvent = new Observable();
	this.modalDropped = new EventEmitter();
};

AngularModal.prototype = {

	id: 0,

	html: '',

	overlay: null,

	input: null,

	zIndex: 1,

	scriptContent: '',

	script: null,

	closable: true,

	settings: null,

	containingForm: false,

	submitFormEvent: null,

	basicZIndex: 10001,

	modalContainer: null,

	modalDropped: null,

	open: function () {
		// Set settings
		if (this.settings) {
			this.setThings(); // LOL
		}

		// Create overlay
		let overlay = document.createElement('div');
		this.overlay = overlay;
		this.setDefaultOverlayStyle(overlay);
		this.overlay.className = 'angular-modal-feature';
		this.overlay.id = this.id;
		if (this.closable) {
			this.overlay.addEventListener('click', (event) => {
				if (event.target === this.overlay) {
					this.close();
				}
			});
		}

		// Create modal container
		this.modalContainer = document.createElement('div');
		this.setDefaultContainerStyle();
		this.modalContainer.innerHTML = this.html;

		// Take control over the forms
		let forms = this.modalContainer.querySelectorAll('form');
		if (forms.length > 0 && this.settings.thrower) {
			this.containingForm = true;
			forms.forEach(form => {
				this.addThrowerInput(form);
				this.addTargetInput(form);
				this.addEntityIdInput(form);
				this.careAboutInputs(form);
				form.addEventListener('submit', event => {
					event.preventDefault();
					let formData = new FormData(event.target);
					let submitter = event.submitter;
					if (submitter && submitter.name && typeof submitter.value !== 'undefined') {
						formData.append(submitter.name, submitter.value)
					}
					this.sendForm(formData);
				});
			});
		}

		// Add js
		this.script = document.createElement('script');
		this.script.textContent = this.wrapJs(this.scriptContent);

		// Build
		this.overlay.prepend(this.modalContainer);
		document.body.prepend(this.overlay);
		this.overlay.append(this.script);

		document.documentElement.style.overflow = 'hidden';

		// Returning Observable for subscribing of form data
		return this.input;
	},

	hasInputForm: function () {
		return this.containingForm;
	},

	setZIndex: function (zIndex) {
		if (!isNaN(zIndex)) {
			this.zIndex = zIndex;
		}
	},

	addJs: function (js) {
		if (this.scriptContent) {
			this.scriptContent += "\n";
		}
		this.scriptContent += js;
	},

	close: function (data = null) {
		if (this.overlay) {
			if (this.modalContainer) {
				this.dropModal();
				let overlay = this.overlay;
				this.modalDropped.subscribe(() => {
					overlay.remove();
					this.overlay = null;
					if (
						Object.values(document.angularModals).length === 0 ||
						Object.values(document.angularModals).every(modalComponent => !modalComponent.overlay)
					) {
						document.documentElement.style.overflow = 'auto';
					}
				});
			} else {
				this.overlay.remove();
				this.overlay = null;
			}
		}
		if (
			Object.values(document.angularModals).length === 0 ||
			Object.values(document.angularModals).every(modalComponent => !modalComponent.overlay)
		) {
			document.documentElement.style.overflow = 'auto';
		}
	},

	setContent: function (html) {
		if (typeof html !== 'string') {
			throw new Error('Modal content must be string, ' + typeof html + ' given!');
		} else {
			this.html = html;
		}
	},

	setClosable: function (toggle = true) {
		this.closable = !!toggle;
	},

	wrapJs: function (content) {
		return `
		(function () {
			const modalId = '${this.id}';
			const modalComponent = document.angularModals[modalId];
			const modalElement = document.getElementById('${this.id}');
			${content}
		})();`;
	},

	setDefaultContainerStyle: function () {
		// node.style.backgroundColor = 'rgb(220, 220, 220)';
		// node.style.padding = '20px';
		this.modalContainer.style.display = 'inline-block';
		this.modalContainer.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.3), 0 6px 20px rgba(0, 0, 0, 0.19)';
		this.modalContainer.style.position = 'relative';
		this.modalContainer.style.transition = 'bottom 0.3s ease-in-out';
		this.modalContainer.style.overflow = 'auto';
		this.modalContainer.style.maxHeight = '90vh';
		this.modalContainer.style.bottom = '0px';
		// node.style.borderRadius = '5px';
	},

	setDefaultOverlayStyle: function (node) {
		node.style.position = 'fixed';
		node.style.top = 0;
		node.style.left = 0;
		node.style.width = '100vw';
		node.style.height = '100vh';
		node.style.backgroundColor = 'rgba(160, 160, 160, 0.8)';
		node.style.zIndex = this.zIndex;
		node.style.display = 'flex';
		node.style.justifyContent = 'center';
    	node.style.alignItems = 'center';
	},

	addSettings: function (settings) {
		this.settings = settings;
	},

	addThrowerInput: function (form) {
		let input = document.createElement('input');
		input.name = 'thrower';
		input.id = 'thrower';
		input.type = 'hidden';
		input.value = this.settings.thrower;
		form.prepend(input);
	},

	addTargetInput: function (form) {
		if (
			this.validateSettings() &&
			this.settings.target &&
			typeof this.settings.target === 'string'
		) {
			let input = document.createElement('input');
			input.name = 'target';
			input.id = 'target';
			input.type = 'hidden';
			input.value = this.settings.target;
			form.prepend(input);
		}
	},

	addEntityIdInput: function (form) {
		if (
			this.validateSettings() &&
			this.settings.entityId &&
			/^\d+$/.test(this.settings.entityId)
		) {
			let input = document.createElement('input');
			input.name = 'entityId';
			input.id = 'entityId';
			input.type = 'hidden';
			input.value = this.settings.entityId;
			form.prepend(input);
		}
	},

	careAboutInputs: function (form) {
		let inputs = form.querySelectorAll('input, select, textarea, button[type="submit"]');
		inputs.forEach(input => {
			if (input.id && !input.name) {
				input.name = input.id;
			}
		});
	},

	setThings: function() {
		if (!this.validateSettings()) {
			console.warn("Settings should be an object, " + typeof this.settings + " given.");
			return;
		}
		if (this.settings.order && !isNaN(this.settings.order)) {
			this.setZIndex(this.basicZIndex + parseInt(this.settings.order));
		}
		if (this.settings && typeof this.settings.closable === 'boolean') {
			this.setClosable(this.settings.closable);
		}
	},

	validateSettings: function () {
		return (this.settings && typeof this.settings === 'object');
	},

	sendForm: function (formData) {
		let http = new HttpClient();
		let formSubmitNotifier = this.submitFormEvent;

		http.vtigerRequest(
			'include/CustomFunctions/Sebok/Classes/Controllers/AfterSaveModal/ModalInputControllerHall.php',
			'POST',
			formData
		).subscribe({
			next: (response) => formSubmitNotifier.next(response),
			error: (response) => formSubmitNotifier.error(response),
			complete: () => formSubmitNotifier.complete()
		});
	},

	dropModal: function () {
		setTimeout(() => {
			this.modalContainer.style.bottom = '20px';
			this.modalContainer.style.transition = 'bottom 0.5s ease-in-out';
		}, 0);
		setTimeout(() => {
			this.modalContainer.style.bottom = '-100vh';
		}, 500);
		setTimeout(() => {
			this.modalDropped.next();
		}, 1000);
	}
};