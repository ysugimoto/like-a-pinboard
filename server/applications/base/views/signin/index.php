<?php echo $this->partial("partial/header");?>

        <section class="lap-content">
            <h2 class="lap-title">Sign in</h2>
            <div class="lap-account pure-g">
                <div class="pure-u-1-2">
                    <a href="<?php echo page_link("signin/twitter");?>" class="pure-button lap-account-button lap-account-twitter">
                        <i class="fa fa-twitter"></i>
                        Sign in with Twitter
                    </a>
                </div>
                <div class="pure-u-1-2">
                    <a href="<?php echo page_link("signin/github");?>" class="pure-button lap-account-button lap-account-github">
                        <i class="fa fa-github-alt"></i>
                        Sign in with Github
                    </a>
                </div>
            </div>
        </section>

<?php echo $this->partial("partial/footer");?>
