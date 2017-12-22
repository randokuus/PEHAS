<!-- Modera.net submenu default -->
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="UTF-8" indent="no"
 omit-xml-declaration="yes"  media-type="text/html"/>

<xsl:param name="structure" select="defaultvalue"/>
<xsl:param name="content" select="defaultvalue"/>
<xsl:param name="publishing_token" select="defaultvalue"/>

<xsl:template match="/">

		<xsl:apply-templates/>

</xsl:template>

<xsl:template match="menu">

	<xsl:apply-templates select="item"/>

</xsl:template>

<xsl:template match="item">
    <xsl:variable name="level" select="count(ancestor::item)+1"/>
    <!-- How many maximum tree elements to show -->
    <xsl:variable name="maxlevels" select="10"/>
    <xsl:variable name="minlevel" select="0"/>

    <!-- OTHER XML elements:
    "nameenc" -> element name, url encoded
    "lead" -> structure element intro text
    -->

    <!-- <xsl:text>&lt;OPTION VALUE = &quot;</xsl:text>
    <xsl:value-of select="@link" />
    <xsl:text>&quot;&gt;</xsl:text>
    <xsl:value-of select="concat('a', name)" />
    <xsl:text>&lt;/OPTION&gt;</xsl:text> -->

    <xsl:variable name="spacer">
    <xsl:choose>
    	<xsl:when test="$level > 1">
    	<xsl:call-template name='repeat-string'>
    	  <xsl:with-param name='str'>&nbsp;&nbsp;&nbsp;</xsl:with-param>
    	  <xsl:with-param name='cnt' select='$level'/>
    	  <xsl:with-param name='pfx'/>
    	</xsl:call-template>
    	</xsl:when>
    	<xsl:otherwise>
    	</xsl:otherwise>
    </xsl:choose>
    </xsl:variable>

    <xsl:element name="option">
    <xsl:attribute name="value"><xsl:value-of select="@link"/></xsl:attribute>

    <xsl:choose>
    	<xsl:when test="@menu = 0">
    	   <xsl:attribute name="style">color: gray;</xsl:attribute>    	   
    	   <xsl:value-of select="concat($spacer,position(), '. ', name)"/>
    	   <xsl:if test="string-length(publishing) &gt; 0">    	   		
    				<xsl:value-of select="concat(' ', $publishing_token, ' ', publishing)"/>    			
    	   </xsl:if>
    	</xsl:when>
    	<xsl:otherwise>    	   
    	   <xsl:value-of select="concat($spacer,position(), '. ', name)"/>
    	   <xsl:if test="string-length(publishing) &gt; 0">    	   		
    				<xsl:value-of select="concat(' ', $publishing_token, ' ', publishing)"/>    			
    	   </xsl:if>
    	</xsl:otherwise>
    </xsl:choose>

    </xsl:element>

    <!-- PROCESS SUBLEVELS, if found, and no more THAN maxlevels, show only active sublevels -->
     <xsl:choose>
    	 <xsl:when test="$level &lt; $maxlevels">

    	 <xsl:choose>
    		 <xsl:when test="count(item) > 0">
    			<xsl:apply-templates select="item"/>
            </xsl:when>
         </xsl:choose>

        </xsl:when>
     </xsl:choose>
     <!-- END PROCESS SUBLEVELS -->

</xsl:template>

<xsl:template name="repeat-string">
  <xsl:param name="str"/><!-- The string to repeat -->
  <xsl:param name="cnt"/><!-- The number of times to repeat the string -->
  <xsl:param name="pfx"/><!-- The prefix to add to the string -->
  <xsl:choose>
    <xsl:when test="$cnt = 0">
      <xsl:value-of select="$pfx"/>
    </xsl:when>
    <xsl:when test="$cnt mod 2 = 1">
      <xsl:call-template name="repeat-string">
	  <xsl:with-param name="str" select="concat($str,$str)"/>
	  <xsl:with-param name="cnt" select="($cnt - 1) div 2"/>
	  <xsl:with-param name="pfx" select="concat($pfx,$str)"/>
	</xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
	<xsl:call-template name="repeat-string">
	  <xsl:with-param name="str" select="concat($str,$str)"/>
	  <xsl:with-param name="cnt" select="$cnt div 2"/>
	  <xsl:with-param name="pfx" select="$pfx"/>
	</xsl:call-template>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>