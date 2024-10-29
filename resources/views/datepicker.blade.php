<div class="otomaties-woocommerce-datepicker">
  @if ($show)
    <h4>{!! $datepickerLabel !!}</h4>
    <input
      class="form-control d-none"
      data-id="{{ $id }}"
      type="text"
      name="otomaties-woocommerce-datepicker--date"
      required
    />
    <input
      class="form-control d-none"
      type="text"
      name="otomaties-woocommerce-datepicker--id"
      value="{{ $id }}"
      required
    />
    <script>
      var datepickerArgs = {!! $datepickerArgs !!};
    </script>
    @if ($useTimeslots)
      <h4>{!! $timeslotLabel !!}</h4>
      <input
        data-datepicker-id="{{ $id }}"
        type="hidden"
        name="otomaties-woocommerce-datepicker--timeslot-date"
        value="{{ $selectedDate }}"
      >
      <select
        class="form-control"
        data-datepicker-id="{{ $id }}"
        name="otomaties-woocommerce-datepicker--timeslot"
        required
      >
        @if (!$selectedDate && count($timeslots) == 0)
          <option value="">{{ __('Select a date first', 'otomaties-woocommerce-datepicker') }}</option>
        @endif
        @foreach ($timeslots as $key => $timeslot)
          <option value="{{ $key }}">{{ $timeslot }}</option>
        @endforeach
      </select>
    @endif
  @endif
</div>
