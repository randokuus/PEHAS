<?php defined("MODERA_KEY")|| die(); ?>            <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

            <!--msgWrap-->
            <div class="msgWrap">
                <p class="msg msgError msgGray">
                    <span><?php echo $this->getTranslate("output|error_occurred"); ?> <?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
                </p>
            </div>
            <!--/msgWrap-->
            <?php }} ?>

            
            <?php if(isset($data["IMESSAGE"]) && is_array($data["IMESSAGE"])){ foreach($data["IMESSAGE"] as $_foreach["IMESSAGE"]){ ?>

            <!--msgWrap-->
            <div class="msgWrap">
                <p class="msg msgOk">
                    <span><?php echo $_foreach["IMESSAGE"]["IMESSAGE"]; ?></span>
                </p>
            </div>
            <!--/msgWrap-->
            <?php }} ?>


            

            <?php if(isset($data["SEARCH"]) && is_array($data["SEARCH"])){ foreach($data["SEARCH"] as $_foreach["SEARCH"]){ ?>

            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <h2>Filter</h2>
                    </div>
                    <!--boxcontent-->
                    <div class="boxcontent">
                        <!--filterTable-->
                        <div class="filterTable threeCols">
                            <form method="post" action="<?php echo $_foreach["SEARCH"]["SELF"]; ?>" name="filter" class="jNice">
                            <?php echo $_foreach["SEARCH"]["HIDDEN"]; ?>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|name_first"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_NAME_FIRST"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|name_last"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_NAME_LAST"]; ?>
                                    </div>
                                </div>
                                <div class="tCol">
                                    <div class="tLine">
                                        <label for=""><?php echo $this->getTranslate("module_isic_user|user_code"); ?>:</label>
                                        <?php echo $_foreach["SEARCH"]["FIELD_FILTER_USER_CODE"]; ?>
                                    </div>
                                </div>
                                <div class="tLineButtons">
                                    <input type="submit" value="<?php echo $this->getTranslate("module_isic_user|search"); ?>" />
                                </div>
                            </form>
                        </div>
                        <!--/filterTable-->
                    </div>
                    <!--/boxcontent-->
                </div>
            </div>
            <!--/box-->
            <?php }} ?>

            
            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <div class="control">
                        </div>
                        <h2><?php echo $this->getGlobals("PAGETITLE"); ?></h2>
                    </div>

                    <!--pagin-->
                    <div class="pagin">
                        <div>
                            <?php echo $data["PAGES"]; ?>
                        </div>
                        <div>
                            <small>(<?php echo $data["RESULTS"]; ?>)</small>
                        </div>
                    </div>
                    <!--/pagin-->

                    <form method="post" action="<?php echo $data["SELF"]; ?>" name="process" id="process">
                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList">
						    <tr>
                                <?php if(isset($data["TITLE"]) && is_array($data["TITLE"])){ foreach($data["TITLE"] as $_foreach["TITLE"]){ ?>

                                <th><span class="<?php echo $_foreach["TITLE"]["CLASS"]; ?>"><a href="<?php echo $_foreach["TITLE"]["URL"]; ?>"><?php echo $_foreach["TITLE"]["NAME"]; ?></a></span></th>
                                <?php }} ?>

						        <th>&nbsp;</th>
						    </tr>
						    <?php if(isset($data["DATA"]) && is_array($data["DATA"])){ foreach($data["DATA"] as $_foreach["DATA"]){ ?>

						    <tr>
						        <td><a href="<?php echo $_foreach["DATA"]["URL_IMAGE"]; ?>"><?php echo $_foreach["DATA"]["IMAGE"]; ?></a></td>
						        <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_NAME_FIRST"]; ?></a></td>
						        <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_NAME_LAST"]; ?></a></td>
						        <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_USER_CODE"]; ?></a></td>
						        <td><a href="<?php echo $_foreach["DATA"]["URL_DETAIL"]; ?>"><?php echo $_foreach["DATA"]["DATA_EMAIL"]; ?></a></td>
						        <td>
						            <div class="wicons">
								        <?php if(isset($_foreach["DATA"]["MOD"]) && is_array($_foreach["DATA"]["MOD"])){ foreach($_foreach["DATA"]["MOD"] as $_foreach["DATA.MOD"]){ ?>
<a href="<?php echo $_foreach["DATA.MOD"]["URL_MODIFY"]; ?>" class="ico iedit" title="Edit">Edit</a><?php }} ?>

								        <?php if(isset($_foreach["DATA"]["DEL"]) && is_array($_foreach["DATA"]["DEL"])){ foreach($_foreach["DATA"]["DEL"] as $_foreach["DATA.DEL"]){ ?>
<a href="<?php echo $_foreach["DATA.DEL"]["URL_DELETE"]; ?>" class="ico idel" title="Delete">Delete</a><?php }} ?>

								    </div>
						        </td>
						    </tr>
						    <?php }} ?>

                        </table>
                    </div>
                    <!--/tableWrap-->
                    </form>

                    <!--pagin-->
                    <div class="pagin">
                        <div>
                            <?php echo $data["PAGES"]; ?>
                        </div>
                        <div>
                            <small>(<?php echo $data["RESULTS"]; ?>)</small>
                        </div>
                    </div>
                    <!--/pagin-->
                </div>
            </div>
            <!--/box-->
