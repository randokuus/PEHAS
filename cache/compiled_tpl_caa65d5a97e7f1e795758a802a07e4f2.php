<?php defined("MODERA_KEY")|| die(); ?>            <?php if(isset($data["NEWS_SEARCHSUB"]) && is_array($data["NEWS_SEARCHSUB"])){ foreach($data["NEWS_SEARCHSUB"] as $_foreach["NEWS_SEARCHSUB"]){ ?>

            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <h2><?php echo $_foreach["NEWS_SEARCHSUB"]["TITLE"]; ?></h2>
                    </div>
                
                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList tResult">
                            <tr>
                                <th>
                                    <span class="">Teema</span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_news|column_title"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_news|date"); ?></span>
                                </th>
                                <th>
                                    <span class=""><?php echo $this->getTranslate("module_news|author"); ?></span>
                                </th>
                            </tr>
                            <?php if(isset($_foreach["NEWS_SEARCHSUB"]["SEARCH_STRUCTURE"]) && is_array($_foreach["NEWS_SEARCHSUB"]["SEARCH_STRUCTURE"])){ foreach($_foreach["NEWS_SEARCHSUB"]["SEARCH_STRUCTURE"] as $_foreach["NEWS_SEARCHSUB.SEARCH_STRUCTURE"]){ ?>

                            <tr>
                                <td>
                                    <span class="icow inews">Uudised</span>
                                </td>
                                <td><a href="<?php echo $_foreach["NEWS_SEARCHSUB.SEARCH_STRUCTURE"]["URL"]; ?>"><?php echo $_foreach["NEWS_SEARCHSUB.SEARCH_STRUCTURE"]["TITLE"]; ?></a></td>
                                <td><?php echo $_foreach["NEWS_SEARCHSUB.SEARCH_STRUCTURE"]["ENTRYDATE"]; ?></td>
                                <td><?php echo $_foreach["NEWS_SEARCHSUB.SEARCH_STRUCTURE"]["AUTHOR"]; ?></td>
                            </tr>
                            <?php }} ?>

                        </table>
                    </div>
                    <!--/tableWrap-->
                </div>
            </div>
            <!--/box-->
            <?php }} ?>

