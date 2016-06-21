{extends file='frontend/register/billing_fieldset.tpl'}

{block name='frontend_register_billing_fieldset_input_street' prepend}
	<div>
		<label for="register_billing_text1" class="normal">EORI Number:</label>
		<input name="register[billing][text1]" type="text" maxlength="17"  id="register_billing_text1" value="{$form_data.text1|escape}" class="text {if $error_flags.text1}instyle_error{/if}" />
	</div>
{/block}