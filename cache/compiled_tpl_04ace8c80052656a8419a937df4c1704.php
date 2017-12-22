<?php defined("MODERA_KEY")|| die(); ?><?php 
$obj = new isic_school;
$ov_24f9116806c97ac3e13f9c1914e9d3dc = $obj->showList();
?>        <!--userpanel-->
        <div class="userpanel">
            <div class="selectw">
                <a href="#" class="icow iuser select">
                    <?php echo $data["MESSAGE"]; ?>
                </a>
                <small><?php echo $data["USER_TYPE"]; ?></small>
                
                <div class="selectopt">
                    <ul>
                        <?php if(isset($data["USER_LIST"]) && is_array($data["USER_LIST"])){ foreach($data["USER_LIST"] as $_foreach["USER_LIST"]){ ?>

                        <li class="<?php echo $_foreach["USER_LIST"]["CLASS"]; ?>">
                            <a href="<?php echo $_foreach["USER_LIST"]["URL"]; ?>" class="icow iuser<?php echo $_foreach["USER_LIST"]["TYPE_ID"]; ?>">
                                <?php echo $_foreach["USER_LIST"]["NAME"]; ?>
                            </a>
                            <small><?php echo $_foreach["USER_LIST"]["TYPE_NAME"]; ?></small>
                        </li>
                        <?php }} ?>

                    </ul>
                </div>
            </div>
        </div>
        <!--/userpanel-->

        <?php echo $ov_24f9116806c97ac3e13f9c1914e9d3dc; ?>        

        <!--userpanel-->
        <div class="userpanel">
            <div class="selectw">
                <a href="<?php echo $data["LOGOUT_URL"]; ?>" class="icow ilogout">
                    <?php echo $data["LOGOUT"]; ?>
                </a>
            </div>
        </div>
        <!--/userpanel-->