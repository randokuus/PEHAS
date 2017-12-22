<?php defined("MODERA_KEY")|| die(); ?><SCRIPT language="JavaScript" type="text/javascript">
function switch_login_type () {
    var auth_type = document.getElementById('auth_type').value;
    var un_row = document.getElementById('username_row');
    var pw_row = document.getElementById('password_row');

    switch (auth_type) {
        case '1': // regular
            un_row.style.visibility = "visible";
            pw_row.style.visibility = "visible";
            document.getElementById('username').select();
            document.getElementById('username').focus();
        break;
        case '2': // id-card (falls through)
        case '3': // Hansa (falls through)
        case '4': // SEB (falls through)
        case '5': // Sampo (falls through)
        case '6': // Nordea (falls through)
        case '7': // Krediidipank (falls through)
        case '8': // LHV (falls through)
            un_row.style.visibility = "visible";
            pw_row.style.visibility = "hidden";
            document.getElementById('username').select();
            document.getElementById('username').focus();
        break;
    }
}
// End -->
</SCRIPT>
    <!--box-->
    <div class="box loginBox">
        <div class="inner">
            <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

            <!--msgWrap-->
            <div class="msgWrap">
                <p class="msg msgError msgGray">
                    <span><?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
                </p>
            </div>
            <!--/msgWrap-->
            <?php }} ?>

        
            <form action="<?php echo $data["SELF"]; ?>" method="post" name="" class="jNice">
            <input type="hidden" name="login" value="true">
            <input type="hidden" name="href" value="<?php echo $data["HREF"]; ?>">
            <input type="hidden" name="login_check" value="true">
            <!--controlTable-->
            <div class="formTable controlTable">
                <!--fRow-->
                <div class="fRow">
                    <!--fLine-->
                    <div class="fLine wLogo">
                        <img src="img/personalikaart_logo.gif" alt="PEHAS" />
                    </div>
                    <!--/fLine-->
                </div>
                <!--/fRow-->

                <!--fRow-->
                <div class="fRow">
                    <!--fLine-->
                    <div class="fLine">
                        <div class="fHead">
                            <?php echo $data["AUTH_TYPE"]; ?>:
                        </div>
                        <div class="fCell">
                            <?php echo $data["FIELD_AUTH_TYPE"]; ?>
                        </div>
                    </div>
                    <!--/fLine-->

                    <!--fLine-->
                    <div class="fLine" id="username_row" style="visibility: hidden;">
                        <div class="fHead">
                            <?php echo $data["USERNAME"]; ?>:
                        </div>
                        <div class="fCell">
                            <input autocomplete="off" type="text" name="username" id="username" value="" />
                        </div>
                    </div>
                    <!--/fLine-->

                    <!--fLine-->
                    <div class="fLine" id="password_row" style="visibility: hidden;">
                        <div class="fHead">
                            <?php echo $data["PASSWORD"]; ?>:
                        </div>
                        <div class="fCell">
                            <input type="password" name="password" id="password" value="" />
                        </div>
                    </div>
                    <!--/fLine-->

                    <!--fSubmit-->
                    <div class="fSubmit">
                        <div class="fSubmitInner">
                            <input type="submit" name="" value="<?php echo $data["BUTTON"]; ?>" />
                        </div>
                    </div>
                    <!--/fSubmit-->
                </div>
                <!--/fRow-->
                <!--fRow-->
                <div class="fRow">
                    <!--fLine-->
                    <div class="fLine">
                        <div class="fHead"></div>
                        <div class="fCell">
                            <a href="<?php echo $this->getTranslate("module_user|conditions_url"); ?>" target="_blank"><?php echo $this->getTranslate("module_user|conditions_title"); ?></a>
                        </div>
                    </div>
                    <!--/fLine-->
                </div>
                <!--/fRow-->

            </div>
            <!--/controlTable-->
            </form>
        </div>
    </div>
    <!--/box-->    