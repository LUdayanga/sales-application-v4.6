<td>
	
		
		 ({{ $variation->sub_sku}})

</td>


<td>
	{!! Form::select('products[' . $product->id . '][unit_id]', $units, $product->unit_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2 input-sm unit_id', 'style' => 'width: 100%;']); !!}
</td>

<td>
	{!! Form::text('products[' . $product->id . '][alert_quantity]', ($product->alert_quantity), ['class' => 'form-control input-sm input_number profit_percent']); !!}
</td>

