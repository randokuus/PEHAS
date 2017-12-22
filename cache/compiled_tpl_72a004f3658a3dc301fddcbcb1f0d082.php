<?php defined("MODERA_KEY")|| die(); ?>            <?php if(isset($data["SEARCH_LIST"]) && is_array($data["SEARCH_LIST"])){ foreach($data["SEARCH_LIST"] as $_foreach["SEARCH_LIST"]){ ?>

            <!--box-->
            <div class="box">
                <div class="inner">
                    <div class="heading">
                        <h2><?php echo $_foreach["SEARCH_LIST"]["TITLE"]; ?></h2>
                    </div>

                    <?php if(isset($_foreach["SEARCH_LIST"]["SEARCHSUB"]) && is_array($_foreach["SEARCH_LIST"]["SEARCHSUB"])){ foreach($_foreach["SEARCH_LIST"]["SEARCHSUB"] as $_foreach["SEARCH_LIST.SEARCHSUB"]){ ?>

                    <!--tableWrap-->
                    <div class="tableWrap">
                        <table class="tList tResult">
                            <tr>
                                <th>
                                    <span class="">Teema</span>
                                </th>
                                <th>
                                    <span class="">Täpsem tulemus</span>
                                </th>
                            </tr>
       					    <?php if(isset($_foreach["SEARCH_LIST.SEARCHSUB"]["SEARCH_STRUCTURE"]) && is_array($_foreach["SEARCH_LIST.SEARCHSUB"]["SEARCH_STRUCTURE"])){ foreach($_foreach["SEARCH_LIST.SEARCHSUB"]["SEARCH_STRUCTURE"] as $_foreach["SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE"]){ ?>

                            <?php if(isset($_foreach["SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE"]["SEARCH_PAGE"]) && is_array($_foreach["SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE"]["SEARCH_PAGE"])){ foreach($_foreach["SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE"]["SEARCH_PAGE"] as $_foreach["SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE.SEARCH_PAGE"]){ ?>

                            <tr>
                                <td>
                                    <span class="icow">Sisulehed</span>
                                </td>
                                <td>
                                    <?php echo $_foreach["SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE.SEARCH_PAGE"]["SEARCH_LINK"]; ?>
                                </td>
                            </tr>
                            <?php }} ?>

					        <?php }} ?>

                        </table>
                    </div>
                    <!--/tableWrap-->
                <?php }} ?>

                </div>
            </div>
            <!--/box-->
            <?php }} ?>
