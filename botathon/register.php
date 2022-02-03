<?php
require('../template/top.php');
require('../template/functions/botathon-funcs.php');

head('Botathon Registration', true);

?>
<style>
	.checkbox-container {
	  display: block;
	  position: relative;
	  padding-left: 35px;
	  margin-bottom: 12px;
	  cursor: pointer;
	  -webkit-user-select: none;
	  -moz-user-select: none;
	  -ms-user-select: none;
	  user-select: none;
	}

	/* Hide the browser's default checkbox */
	.checkbox-container input {
	  position: absolute;
	  opacity: 0;
	  cursor: pointer;
	  height: 0;
	  width: 0;
	}

	/* Create a custom checkbox */
	.checkmark {
	  position: absolute;
	  top: 0;
	  left: 0;
	  height: 25px;
	  width: 25px;
	  background-color: #eee;
	}

	/* On mouse-over, add a grey background color */
	.checkbox-container:hover input ~ .checkmark {
	  background-color: #ccc;
	}

	/* When the checkbox is checked, add a blue background */
	.checkbox-container input:checked ~ .checkmark {
	  background-color: #45cd8f;
	}

	/* Create the checkmark/indicator (hidden when not checked) */
	.checkmark:after {
	  content: "";
	  position: absolute;
	  display: none;
	}

	/* Show the checkmark when checked */
	.checkbox-container input:checked ~ .checkmark:after {
	  display: block;
	}

	/* Style the checkmark/indicator */
	.checkbox-container .checkmark:after {
	  left: 9px;
	  top: 5px;
	  width: 5px;
	  height: 10px;
	  border: solid white;
	  border-width: 0 3px 3px 0;
	  -webkit-transform: rotate(45deg);
	  -ms-transform: rotate(45deg);
	  transform: rotate(45deg);
	}
	
	.select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
		color: #686868;
		padding: 18px;
	}
</style>
<main class="page-content">
        <!-- Classic Breadcrumbs-->
        <section class="breadcrumb-classic">
          <div class="rd-parallax">
            <div data-speed="0.25" data-type="media" data-url="/images/hackathon-demo.jpg" class="rd-parallax-layer"></div>
            <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
              <div class="shell">
                <ul class="list-breadcrumb">
                  <li><a href="/">Home</a></li>
                  <li><a href="/botathon">Botathon</a></li>
                  <li>Register</li>
                </ul>
              </div>
            </div>
          </div>
        </section>
        <section class="section-50">
          <div class="shell range-offset-1">
            <div class="range">
              <div class="cell-lg-6">
                <h1>Register</h1>
                  <!--<h6>Registration is over for the year. Information about season 3 will be relased during the spring semester 2022. </h6>-->
                  <h6>You may sign up for our <strong>Botathon Season 3</strong> competition below.</h6>
              </div>
				
				<div>
                    <h4><small>Registration is open for all <span style="color:green;">UNT</span> students.</small><br><strong>Spots remaining: <span style="color:red;"><?php
						echo botathon_spots_remaining();
						?></span></strong></h4>
				</div>
            </div>
			  <div class="shell text-left">
				<form data-form-output="form-output-global" data-form-type="registration" method="post" action="/ajax/botathon-registration" class="rd-mailform text-left" novalidate="novalidate">
				  <div class="range offset-top-40 offset-md-top-60">
					<div class="cell-lg-4 cell-md-6">
					  <div class="form-group postfix-xl-right-40">
							<label for="registrant_name" class="form-label rd-input-label">Name</label>
							<input id="registrant_name" type="text" name="registrant_name" data-constraints="@Required" class="form-control form-control-has-validation form-control-last-child">
					  </div>
					  <div class="form-group postfix-xl-right-40">
							<label for="major" class="form-label rd-input-label">Major</label>
							<input id="major" type="text" name="major" data-constraints="@Required" class="form-control form-control-has-validation form-control-last-child">
						  	<span class="form-validation"></span>
					  </div>
					</div>
					<div class="cell-lg-4 cell-md-6 offset-top-20 offset-md-top-0">
					  <div class="form-group postfix-xl-right-40">
							<label for="email" class="form-label rd-input-label">E-mail <em>(@my.unt.edu)</em></label>
							<input id="email" type="email" name="email" data-constraints="@Email @Required" class="form-control form-control-has-validation form-control-last-child">
					  </div>
					  <div class="form-group postfix-xl-right-40">
						  <select id="gender" name="gender" data-constraints="@Required" class="form-control form-control-has-validation form-control-last-child">
							<option>Please select your gender...</option>
						    <option value="male">Male</option>
						    <option value="female">Female</option>
						    <option value="other">Other</option>
						  </select>
					  </div>
					</div>
					<div class="cell-lg-4 offset-top-20 offset-lg-top-0">
					  <div class="form-group postfix-xl-right-40">
							<label for="phone_number" class="form-label rd-input-label">Phone Number</label>
							<input id="phone_number" type="text" name="phone_number" data-constraints="@Required" class="form-control form-control-has-validation form-control-last-child">
						  	<span class="form-validation"></span>
					  </div>
					  <div class="form-group postfix-xl-right-40">
						  <select id="classification" name="classification" data-constraints="@Required" class="form-control form-control-has-validation form-control-last-child">
							<option>Please select your classification...</option>
						    <option value="freshman">Freshman</option>
						    <option value="sophomore">Sophomore</option>
						    <option value="junior">Junior</option>
							<option value="senior">Senior</option>
							<option value="postgraduate">Postgraduate</option>
						  </select>
					  </div>
					</div>
				  </div>

				  <div class="range" style="margin-top: 20px;">
                      <div class="cell-lg-4 cell-md-6 offset-top-20 offset-md-top-0">
                          <div class="form-group postfix-xl-right-40">
                              <label for="team_name" class="form-label rd-input-label">Team Name <em><small>(Optional, and you can change this later)</small></em></label>
                              <input id="team_name" type="text" name="team_name" class="form-control form-control-has-validation form-control-last-child">
                              <span class="form-validation"></span>
                          </div>
                      </div>
					<div class="cell-lg-4 cell-md-6 offset-top-20 offset-md-top-0">
					  <div class="form-group postfix-xl-right-40">
							<label for="diet_restrictions" class="form-label rd-input-label">Dietary Restrictions</label>
							<input id="diet_restrictions" type="text" name="diet_restrictions" class="form-control form-control-has-validation form-control-last-child">
						  	<span class="form-validation"></span>
					  </div>
					</div>
					<div class="cell-lg-4 cell-md-6 offset-top-20 offset-md-top-0">
					  <div class="form-group postfix-xl-right-40">
							<label for="unteuid" class="form-label rd-input-label">UNT EUID</label>
							<input id="unteuid" type="text" name="unteuid" data-constraints="@Required" class="form-control form-control-has-validation form-control-last-child">
						  	<span class="form-validation"></span>
					  </div>
					</div>

					<div class="cell-lg-4 cell-md-6">
                        <div style="padding-top: 21px;">
					  <div class="form-group postfix-xl-right-40">

                              <!--<label class="checkbox-container">I am allergic to Latex.
                                <input id="latex_allergy" name="latex_allergy" type="hidden" value="off" class="form-control form-control-has-validation form-control-last-child">
                                <span class="checkmark"></span>
                              </label>-->
                              <label for="disability_accommodations" class="form-label rd-input-label">Disability Accommodations <em><small>(Optional)</small></em></label>
                              <input id="disability_accommodations" type="text" name="disability_accommodations" class="form-control form-control-has-validation form-control-last-child">
                              <span class="form-validation"></span>
                          </div>
					  </div>

					</div>

				  </div>
					
					<center>
						<div style="padding: 40px 0px;">
							<div style="max-width: 525px;">
							<label class="checkbox-container"> I understand that if I do not attend the event without notifying organisers, I will be taking a spot away from someone else.
							  <input id="promise" name="promise" type="checkbox" data-constraints="@Required" class="form-control form-control-has-validation form-control-last-child">
							  <span class="checkmark"></span>
							</label>
							</div>
						</div>
						<button type="submit" class="btn btn-form btn-default">Sign Up</button>
					</center>

				  </form>
			  </div>
          </div>
        </section>
      </main>
<?php
footer();
?>