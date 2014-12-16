<?php echo $this->partial("partial/header");?>
        <section class="lap-content">
            <h2 class="lap-title">Sign in</h2>
            <section class="lap-token">
                <h3>This server URL:</h3>
                <p class="lap-info-value">
                    <input type="text" value="http://example.com" readonly>
                </p>
                <h3>Your token:</h3>
                <p class="lap-info-value blank">
                    <input type="texr" value="Token has not generated yet" readonly>
                </p>
            </section>
            <div class="lap-account pure-g">
                <div class="pure-u-2-3">
                    <button class="pure-button lap-account-button lap-account-twitter">
                        <i class="fa fa-twitter"></i>
                        Sign in with Twitter
                    </button>
                </div>
                <div class="pure-u-1-3 lap-account-authorize">
                    <span>Authorized</span>
                </div>
            </div>
            <div class="lap-account pure-g">
                <div class="pure-u-2-3">
                    <button class="pure-button lap-account-button lap-account-github">
                        <i class="fa fa-github-alt"></i>
                        Sign in with Github
                    </button>
                </div>
                <div class="pure-u-1-3 lap-account-authorize">
                    <span>Authorized</span>
                </div>
            </div>
        </section>
<?php echo $this->partial("partial/footer");?>
