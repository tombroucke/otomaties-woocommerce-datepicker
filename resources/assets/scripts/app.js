import flatpickr from "flatpickr";

class Datepicker {
	constructor(el) {
		this.el = el;
		this.id = el.dataset.id;
		this.timepicker = document.querySelector('[name="otomaties-woocommerce-datepicker--timeslot"][data-datepicker-id="' + this.id + '"]');
		this.timepickerDate = document.querySelector('[name="otomaties-woocommerce-datepicker--timeslot-date"][data-datepicker-id="' + this.id + '"]');
		this.maybeAddEmptyTimeslotsOption();
		this.bindEvents();
	}

	options() {
		const defaultOptions = {
			firstDayOfWeek: 1,
			locale: 'fr',
			selectedDate: null,
			minDate: null,
			maxDate: null,
			enabledDates: [],
			disabledDates: [],
			disabledDays: [],
		};
		return Object.assign(defaultOptions, datepickerArgs);
	}

	bindEvents() {
		const self = this;
		document.body.addEventListener('js_updated_checkout', function() {
			self.initFlatpickr();
		});
	}

	isDisabledPipeline(date) {
		const enabledDates = this.options().enabledDates;
		const disabledDates = this.options().disabledDates;
		const disabledDays = this.options().disabledDays;
		const isoDate = this.toISOString(date);

		if (enabledDates.includes(isoDate)) {
			return false;
		}

		// test if date is bigger than mindate
		if (this.options().minDate) {
			const minDate = new Date(this.options().minDate);
			// with correct timezone
			minDate.setHours(0, 0, 0, 0);
			if (date < minDate) {
				return true;
			}
		}
		
		if (disabledDates.includes(isoDate)) {
			return true;
		}
		
		if (disabledDays.includes(date.getDay())) {
			return true;
		}

		return false;

	}

	initFlatpickr() {
		const datepicker = this;
		const locale = this.options().locale;
		import('flatpickr/dist/l10n/' + locale + '.js').then(() => {
			flatpickr.localize(flatpickr.l10ns[locale]);
			const flatpickrInstance = flatpickr(this.el, {
				disable: [
					function (date) {
						return datepicker.isDisabledPipeline(date);
					}
				],
				onChange: function(selectedDates, dateStr, instance) {
					if (!datepicker.timepicker) {
						return;
					}
					const timeslotRestRoute = otomWcDatepicker.timeslotRestRoute;
					const date = selectedDates[0];

					fetch(timeslotRestRoute + '?date=' + datepicker.toISOString(date) + '&datepicker_id=' + datepicker.id, {
						method: 'GET',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': otomWcDatepicker.nonce,
						},
					})
					.then(response => response.json())
					.then(data => {
						const timeslotOptions = data.map(function(timeslot) {
							return '<option value="' + timeslot + '">' + timeslot + '</option>';
						});
						datepicker.timepicker.innerHTML = timeslotOptions.join('');
						datepicker.maybeAddEmptyTimeslotsOption();
						datepicker.timepickerDate.value = datepicker.toISOString(date);
					});
				},
				onReady: function(selectedDates, dateStr, instance) {
					let changedMonth = 0;
					while(!datepicker.monthHasEnabledDate(instance)) {
						instance.changeMonth(1);
						changedMonth++;
						if (changedMonth > 24) { // Prevent infinite loop
							break;
						}
					}
				},
				minDate: datepicker.options().minDate,
				maxDate: datepicker.options().maxDate,
				inline: true,
				locale: {
					firstDayOfWeek: datepicker.options().firstDayOfWeek ? datepicker.options().firstDayOfWeek : 1,
				},
				defaultDate: datepicker.options().selectedDate,
			});	
		});
	}

	maybeAddEmptyTimeslotsOption() {
		if (!this.timepicker) {
			return;
		}
		const timeslotOptions = this.timepicker.querySelectorAll('option');
		if (timeslotOptions.length === 0) {
			this.timepicker.innerHTML = '<option value="">' + otomWcDatepicker.noTimeslotsAvailable + '</option>';
		}
	}

	monthHasEnabledDate(instance) {
		let firstEnabledDate = null;
		const month = instance.currentMonth;
		const year = instance.currentYear;
		const daysInMonth = new Date(year, month + 1, 0).getDate();
		for (let day = 1; day <= daysInMonth; day++) {
			const date = new Date(year, month, day);
			
			if (!this.isDisabledPipeline(date)) {
				firstEnabledDate = date;
				break;
			}
		}
		return firstEnabledDate;
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
