<?php
require('../../template/top.php');
require('../../template/functions/payment_button.php');

$valid_tshirt_sizes = 		array('XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL');
$shirt_costs_members = 		array(10,   10,  10,  10,  10,   12,    12,    12);
$shirt_costs_non_members = 	array(15,   15,  15,  15,  15,   17,    17,    17);

$size = "";
$cost = 0;
$member = false;

if (isset($_GET['code'])) {
	if ($_GET['code'] == 'r5tmp') {
		$member = true;
	}
}

do {
	if (isset($_GET['size']) && isset($_GET['colour'])) {
		$size = strtoupper($_GET['size']);
		$colour = strtoupper($_GET['colour']);
		if (in_array($size, $valid_tshirt_sizes)) {
			if ($member) {
				$cost = $shirt_costs_members[array_search($size, $valid_tshirt_sizes)];
			} else {
				$cost = $shirt_costs_non_members[array_search($size, $valid_tshirt_sizes)];
			}
			break;
		}
	}
	header('Location: /merch/shirts');
	die();
} while (false);

head('Buy T-Shirt', true);
?>

<main class="page-content">
        <section class="section-50">
          <div class="shell">
            <div class="range range-md-justify">
              <div class="cell-md-12">
                <div class="inset-md-right-30 inset-lg-right-0 text-center">
                  <h1>Buy T-Shirt</h1>

					<p><strong>Please fill out the information below and then click Buy Now.</strong></p>
					
					<p>
						<strong style="font-size: 20px;">
							<pre style="display: inline-block;">Cost: $<?php echo $cost; ?><br>Size: <?php echo $size; ?><br>Colour: <?php echo $colour; ?></pre>
						</strong>
					</p>
					
					<?php
					
					$custom = serialize(array(
						'id' => 'MERCH_CLUB_TSHIRT',
						'uid' => -1
					));
					
					$button = payment_button(
							'T-Shirt', 
							$cost,
							$custom = 'MERCH_CLUB_TSHIRT',
							$opt_names = array('Size', 'Colour'),
							$opt_vals = array($size, $colour),
							$quantity = 1,
							$complete_return_uri = '/merch/buy/complete'
						);
					echo $button['btn'];
					?>
					
				  </div>
				</div>
			  </div>
			</div>
	</section>
</main>

<?php
footer(false);
?>