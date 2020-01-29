<?php
require('../template/top.php');
head('Pay Dues', true);
?>

<main class="page-content">
        <section class="section-75 section-md-100 section-lg-150">
          <div class="shell">
            <div class="range range-md-justify">
              <div class="cell-md-12">
                <div class="inset-md-right-30 inset-lg-right-0 text-center">
                  <h1>Pay Dues</h1>

					<p><strong>Please fill out the information below and then click Pay Now.</strong></p>

					<p><strong style="font-size: 20px;"><pre style="display: inline-block;">Cost: $20</pre></strong></p>

					<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="6UME3HSZNR3FU">
					<table style="display: inline-block;">
						<input type="hidden" name="custom" value="DUES_PAYMENT">
						<tr><td><input type="hidden" name="on2" value="Your Name">Your Name</td></tr><tr><td><input type="text" name="os2" maxlength="200"></td></tr>
						<tr><td><input type="hidden" name="on0" value="UNT EUID">UNT EUID</td></tr><tr><td><input type="text" name="os0" maxlength="200"></td></tr>
						<tr><td><input type="hidden" name="on1" value="UNT E-mail Address">UNT E-mail Address</td></tr><tr><td><input type="text" name="os1" maxlength="200"></td></tr>
					</table>
						<br>
					<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_paynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>

				  </div>
				</div>
			  </div>
			</div>
	</section>
</main>

<?php
footer(false);
?>

<script>
	$('input[src="https://www.paypalobjects.com/en_US/i/btn/btn_paynowCC_LG.gif"]').on('click', function(e) {
		return true;
		if (!$('input[name="os0"]').val() || !$('input[name="os1"]').val() || !$('input[name="os2"]').val()) {
			alert('Please fill out your name, EUID & e-mail address.');
			e.preventDefault();
			return false;
		}
	});
</script>
