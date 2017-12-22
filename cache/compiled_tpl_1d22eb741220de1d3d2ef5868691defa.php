<?php defined("MODERA_KEY")|| die(); ?>        <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

        <!--msgWrap-->
        <div class="msgWrap">
            <p class="msg msgError msgGray">
                <span><?php echo $this->getTranslate("output|error_occurred"); ?> <?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
            </p>
        </div>
        <!--/msgWrap-->
        <?php }} ?>


        

        <!--singleCol-->
        <div class="singleCol">
	        <!--box-->
	        <div class="box">
	            <div class="inner">
	                <div class="heading">
	                    <h2><?php echo $this->getGlobals("PAGETITLE"); ?></h2>
	                </div>
	
	             <!--formTable-->
	             <div class="formTable shortTable">
	                 <form name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" enctype="multipart/form-data" class="jNice">
	                     <?php echo $data["HIDDEN"]; ?>
	                     <!--fRow-->
	                     <div class="fRow">
	                     <?php if(isset($data["FIELDS"]) && is_array($data["FIELDS"])){ foreach($data["FIELDS"] as $_foreach["FIELDS"]){ ?>

	                     <!--fLine-->
	                     <div class="fLine">
	                         <div class="fHead">
	                             <?php echo $_foreach["FIELDS"]["TITLE"]; ?>:
	                         </div>
	                         <div class="fCell">
	                             <?php echo $_foreach["FIELDS"]["DATA"]; ?>
	                         </div>
	                     </div>
	                     <!--/fLine-->
	                     <?php }} ?>

	                     </div>
	                     <!--/fRow-->
	                     <!--fSubmit-->
	                     <div class="fSubmit">
	                         <div class="fSubmitInner">
	                         <input type="submit" value="<?php echo $data["BUTTON"]; ?>" />
	                         </div>
	                     </div>
	                     <!--/fSubmit-->
	                 </form>
	             </div>
	             <!--/formTable-->
	            </div>
	        </div>
	        <!--/box-->
        </div>
        <!--/singleCol-->
