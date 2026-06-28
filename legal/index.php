<?php
require("../template/top.php");
head("Legal", true);
?>

<section class="breadcrumb-classic">
    <div class="shell">
        <ul class="list-breadcrumb">
            <li><a href="/">Home</a></li>
            <li>Legal</li>
        </ul>
    </div>
</section>

<section class="section-50 section-md-75 section-lg-100">
    <div class="shell text-sm-left">
        <div class="range range-md-justify">
            <div class="cell-lg-9 cell-xl-8">
                <h2>Legal</h2>
                <p class="text-gray-dark">Policies and guidelines for UNT Robotics and its online communities.</p>

                <div class="range offset-top-30">
                    <div class="cell-md-6 offset-bottom-30">
                        <div class="panel panel-default" style="height: 100%;">
                            <div class="panel-body">
                                <h4 class="offset-top-0"><a href="/legal/privacy">Privacy Policy</a></h4>
                                <p class="text-gray">How we collect, use, and protect your personal information when you use our website and services.</p>
                                <a href="/legal/privacy" class="btn btn-sm btn-default">Read policy</a>
                            </div>
                        </div>
                    </div>
                    <div class="cell-md-6 offset-bottom-30">
                        <div class="panel panel-default" style="height: 100%;">
                            <div class="panel-body">
                                <h4 class="offset-top-0"><a href="/legal/discord-rules">Discord Rules</a></h4>
                                <p class="text-gray">The rules and code of conduct that govern our official Discord community.</p>
                                <a href="/legal/discord-rules" class="btn btn-sm btn-default">Read rules</a>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-gray offset-top-20" style="font-size: 13px;">
                    Questions about any of these policies? Email <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>.
                </p>
            </div>
        </div>
    </div>
</section>

<?php
footer();
?>
