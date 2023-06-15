<div class="otomaties-woocommerce-datepicker">
	@if($show)
		<h4>{!! $label !!}</h4>
		<input type="text" class="form-control d-none" name="otomaties-woocommerce-datepicker--date" required />
		<input type="text" class="form-control d-none" name="otomaties-woocommerce-datepicker--id" value="{{ $id }}" required/>
		<script>
			var datepickerArgs = {!! $datepickerArgs !!};
		</script>
	@endif
</div>
