import flatpickr from "flatpickr";

class Datepicker {
	constructor(el) {
		this.el = el;
		this.bindEvents();
	}

	options() {
		const defaultOptions = {
			firstDayOfWeek: 1,
			locale: 'fr',
			selectedDate: null,
		};

		return Object.assign(defaultOptions, datepickerArgs);
	}

	bindEvents() {
		const self = this;
		document.body.addEventListener('js_updated_checkout', function() {
			self.initFlatpickr();
		});
	}

	initFlatpickr() {
		const datepicker = this;
		const locale = this.options().locale;
		import('flatpickr/dist/l10n/' + locale + '.js').then(() => {
			flatpickr.localize(flatpickr.l10ns[locale]);
			
			const tempPickr = flatpickr(this.el, {
				inline: true,
				enable: [],
			});
			datepicker.getEnabledDates(tempPickr.currentYear, tempPickr.currentMonth + 1).then((defaultEnabledDates) => {
				flatpickr(this.el, {
					onMonthChange: function(selectedDates, dateStr, instance) {
						datepicker.getEnabledDates(instance.currentYear, instance.currentMonth + 1).then((enabledDates) => {
							instance.set('enable', enabledDates);
						});
					},
					onYearChange: function(selectedDates, dateStr, instance) {
						datepicker.getEnabledDates(instance.currentYear, instance.currentMonth + 1).then((enabledDates) => {
							instance.set('enable', enabledDates);
						});
					},
					enable: [function (date) {
						const isoDate = datepicker.toISOString(date);
						return defaultEnabledDates.includes(isoDate);
					}],
					minDate: datepicker.options().minDate,
					inline: true,
					locale: {
						firstDayOfWeek: datepicker.options().firstDayOfWeek ? datepicker.options().firstDayOfWeek : 1,
					},
					defaultDate: datepicker.options().selectedDate,
				});
			});

		});
		  
	}

	getEnabledDates(year, month) {
		const datepickerId = this.options().id;

		const endpoint = '/wp-json/otomaties-woocommerce-datepicker/v1/datepicker/' + datepickerId + '/enabled-dates';
		const queryParams = new URLSearchParams({
			year: year,
			month: month,
		});

		return fetch(`${endpoint}?${queryParams}`)
			.then(response => response.json())
			.then(data => {
				return data;
			})
			.catch(error => {
				return [];
			});
	}

	toISOString(date) {
		const year = date.getFullYear();
		const month = date.getMonth() + 1; // Note: Month is zero-based, so we add 1
		const day = date.getDate();
		const isoDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
		return isoDate;
	}
}

function initDatepickers(initFlatpickr = true) {
	const datepickerElements = document.querySelectorAll('.otomaties-woocommerce-datepicker input[name="otomaties-woocommerce-datepicker--date"]');
	for (const datepickerElement of datepickerElements) {
		const datepicker = new Datepicker(datepickerElement);
		if (initFlatpickr) {
			datepicker.initFlatpickr();
		}
	}
}

document.body.addEventListener('js_updated_checkout', function() {
	initDatepickers();
});

initDatepickers(false);
