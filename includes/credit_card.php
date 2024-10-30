<?php if(trim($this->merchant_id) == '' || trim($this->terminal_id) == '' || trim($this->secret_key) == '') { ?>
	<div>
		<fieldset id="chargeanywhere-c-fields">
			Setup is not complete. Please contact the site administrator
		</fieldset>
	</div>
<?php } else { ?>
	<?php if($this->accept_credit == 'yes' && $this->accept_ach == 'yes'){?>
		<div id="selected_option" style="margin:10px 0px;">
			<select name="chargeanywhere_option" id="chargeanywhere_option" class="input-text  wc-credit-card-form-card-number" onchange="showSelectedOption(this.value)" style="width:100%;">
				<option value="credit">Credit</option>
				<option value="ach">ACH</option>
			</select>
		</div>
	<?php } else if($this->accept_credit == 'yes'){ ?>
		<input type="hidden" name="chargeanywhere_option" id="chargeanywhere_option"  value="credit" />
	<?php } else {?>
		<input type="hidden" name="chargeanywhere_option" id="chargeanywhere_option"  value="ach" />
	<?php } ?>

	<?php if($this->accept_credit != 'yes' && $this->accept_ach != 'yes'){?>
		<div>
			<fieldset id="chargeanywhere-c-fields">
				Please enable options to proceed with Charge Anywhere
			</fieldset>
		</div>
	<?php } ?>

	<?php if(($this->accept_credit == 'yes' && $this->accept_ach != 'yes') || ($this->accept_credit == 'yes' && $this->accept_ach == 'yes')){?>
	<div id="credit-card" class="chargeanywhere-credit-card">
		<?php if($this->mode == 'true') { ?>
			<p>TEST MODE ENABLED: Use test card number 4111111111111111 with any 3-digit CVC and a future expiration date</p>
		<?php } ?>
		<fieldset id="chargeanywhere-cc-fields">
			<p class="form-row form-row-wide">
				<label for="chargeanywhere-card-number"><?php esc_html_e( 'Card Number ', 'woocommerce-cardpay-chargeanywhere' ) ?><span class="required">*</span></label>
				<input id="chargeanywhere-card-number" value="" class="input-text credit-card-form-card-number" type="text" autocomplete="off" placeholder="•••• •••• •••• ••••" name="chargeanywhere-card-number" />
			</p>
			<p class="form-row form-row-first">
				<label for="chargeanywhere-card-expiry"><?php esc_html_e( 'Expiry (MM/YY) ', 'woocommerce-cardpay-chargeanywhere' ) ?><span class="required">*</span></label>
				<input id="chargeanywhere-card-expiry" value="" class="input-text credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="MM / YY" name="chargeanywhere-card-expiry" />
			</p>
			<p class="form-row form-row-last">
				<label for="chargeanywhere-card-cvc"><?php esc_html_e( 'Card Code ', 'woocommerce-cardpay-chargeanywhere' ) ?><span class="required">*</span></label>
				<input id="chargeanywhere-card-cvc" value="" class="input-text credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="chargeanywhere-card-cvc" />
			</p>
		</fieldset>
	</div>
	<?php } ?>
	<?php 
	$show_block = 'block';
	if($this->accept_credit == 'yes' && $this->accept_ach == 'yes')
		$show_block = 'none';
	?>
	<?php if(($this->accept_credit != 'yes' && $this->accept_ach == 'yes') || ($this->accept_credit == 'yes' && $this->accept_ach == 'yes')){?>
		<div id="ach-card" class="chargeanywhere-ach-card" style="display:<?php echo $show_block;?>">
			<?php if($this->mode == 'true') { ?>	
				<p>TEST MODE ENABLED: Use test account number 123456 with routing number 111111118</p>
			<?php } ?>
			<fieldset id="chargeanywhere-ach-fields">
				<p class="form-row form-row-wide">
					<label for="chargeanywhere-account-number"><?php esc_html_e( 'Account Number', 'woocommerce-cardpay-chargeanywhere' ) ?><span class="required">*</span></label>
					<input id="chargeanywhere-account-number" value="" class="input-text" type="text" maxlength="29" autocomplete="off" placeholder="••• ••• ••• •••" name="chargeanywhere-account-number" />
				</p>
				<p class="form-row form-row-wide">
					<label for="chargeanywhere-routing-number"><?php esc_html_e( 'Routing Number', 'woocommerce-cardpay-chargeanywhere' ) ?><span class="required">*</span></label>
					<input id="chargeanywhere-routing-number" value="" class="input-text" type="text" maxlength="11" autocomplete="off" placeholder="••• ••• •••" name="chargeanywhere-routing-number" />
				</p>
				<p class="form-row form-row-wide">
					<label for="chargeanywhere-acctype"><?php esc_html_e( 'Account Type', 'woocommerce-cardpay-chargeanywhere' ) ?><span class="required">*</span></label>
					<select name="chargeanywhere-acctype" id="chargeanywhere-acctype" class="input-text">
						<option value="0">Personal Savings</option>
						<option value="1">Personal Checking</option>
						<option value="2">Business Checking</option>
					</select>
				</p>
			</fieldset>
		</div>
	<?php } ?>
<?php } ?>