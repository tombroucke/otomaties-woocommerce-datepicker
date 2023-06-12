<div class="otomaties-woocommerce-datepicker">
	@if($show)
		<h4>{!! $label !!}</h4>
		<input type="text" class="form-control d-none" name="otomaties-woocommerce-datepicker" required />
		<script>
			var datepickerArgs = {!! $datepickerArgs !!};
		</script>
	@endif
</div>
