<?php defined("MODERA_KEY")|| die(); ?>        <!--userpanel-->
        <div class="userpanel">
            <div class="selectw">
                <a href="#" class="icow ihome select">
                    <?php echo $data["CURRENT_SCHOOL"]; ?>
                </a>
                <div class="selectopt">
                    <ul>
                        <?php if(isset($data["SCHOOL"]) && is_array($data["SCHOOL"])){ foreach($data["SCHOOL"] as $_foreach["SCHOOL"]){ ?>

                        <li class="<?php echo $_foreach["SCHOOL"]["CLASS"]; ?>">
                            <a href="<?php echo $_foreach["SCHOOL"]["URL"]; ?>" class="icow ihome<?php echo $_foreach["SCHOOL"]["TYPE"]; ?>">
                                <?php echo $_foreach["SCHOOL"]["NAME"]; ?>
                            </a>
                        </li>
                        <?php }} ?>

                    </ul>
                </div>
            </div>
        </div>
        <!--/userpanel-->
