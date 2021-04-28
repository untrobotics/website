<?php
require('../template/top.php');
head('My Profile', true, true);
global $userinfo
?>

<main class="page-content">
	<!-- Classic Breadcrumbs-->
	<section class="section-50">
	  <div class="shell text-sm-left">
		<div class="blog-post">
            <div class="container">
                <div class="row">
                    <div class="col-sm" style="margin-bottom: 30px;">
                        <div class="cell-md-preffix-2 cell-lg-9 cell-md-10 cell-xl-8">
                            <h2>Your Profile</h2>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <h6>Name: </h6>
                    </div>
                    <div class="col-sm-8" style="margin-bottom: 10px;">
                        <h6><?php echo $userinfo['name']; ?></h6>
                    </div>

                    <div class="col-sm-4">
                        <h6>UNT EUID: </h6>
                    </div>
                    <div class="col-sm-8" style="margin-bottom: 10px;">
                        <h6><?php echo $userinfo['unteuid']; ?></h6>
                    </div>

                    <div class="col-sm-4">
                        <h6>Email Address: </h6>
                    </div>
                    <div class="col-sm-7" style="margin-bottom: 10px;">
                        <h6><?php echo $userinfo['email']; ?></h6>
                    </div>
                    <div class="col-sm-1">
                        <h6><a href="#" class="ioon icon-sm icon-darker  fa-pencil"></a></h6>
                    </div>

                    <div class="col-sm-4">
                        <h6>Phone Number: </h6>
                    </div>
                    <div class="col-sm-7" style="margin-bottom: 10px;">
                        <h6><?php echo substr($userinfo['phone'],-10,3); ?> - <?php echo substr($userinfo['phone'],-7,3); ?> - <?php echo substr($userinfo['phone'],-4,4); ?></h6>
                    </div>
                    <div class="col-sm-1">
                        <h6><a href="#" class="ioon icon-sm icon-darker  fa-pencil"></a></h6>
                    </div>

                    <div class="col-sm-4">
                        <h6>Graduation Term: </h6>
                    </div>
                    <div class="col-sm-7" style="margin-bottom: 10px;">
                        <h6><?php if($userinfo['grad_term'] == 0){echo 'Spring';}else{echo 'Winter';} ?> <?php echo $userinfo['grad_year']; ?></h6>
                    </div>
                    <div class="col-sm-1">
                        <h6><a href="#" class="ioon icon-sm icon-darker  fa-pencil"></a></h6>
                    </div>

                    <div class="col-sm-4">
                        <h6>Discord Account: </h6>
                    </div>
                    <div class="col-sm-7" style="margin-bottom: 10px;">
                        <h6><?php if(is_null($userinfo['discord_id'])){echo 'Null';}else{echo $userinfo['discord_id'];} ?></h6>
                    </div>
                    <div class="col-sm-1">
                        <h6><a href="#" class="ioon icon-sm icon-darker  fa-times"></a></h6>
                    </div>

                    <div class="col-sm-4">
                        <h6>Good Standing: </h6>
                    </div>
                    <div class="col-sm-8" style="margin-bottom: 10px;">
                        <h6><?php echo $userinfo['sandbox']; ?></h6>
                    </div>

                    <div class="col-sm-4">
                        <h6>Official Member: </h6>
                    </div>
                    <div class="col-sm-8" style="margin-bottom: 10px;">
                        <h6><?php echo $userinfo['sandbox']; ?></h6>
                    </div>

                    <div class="col-sm-12">
                        <div class="cell-xs-12">
                            <div class="left-aside"><span class="small text-darker text-uppercase text-bold text-spacing-340"><?php echo $userinfo['name']; ?></span>
                                <div class="divider-custom veil reveal-md-block"></div>
                                <ul class="list text-md-center">
                                    <li><a href="#" class="ioon icon-sm icon-darker fa-check"></a></li>
                                    <li><a href="#" class="ioon icon-sm icon-darker fa-times"></a></li>
                                </ul>
                            </div>
                    </div>
                    <div class="col-sm">
                        <?php var_dump($userinfo); ?>
                    </div>
                </div>
            </div>
		</div>
	  </div>
	</section>
  </main>

<?php
footer();
?>