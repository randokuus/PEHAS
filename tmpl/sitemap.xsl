<!-- Modera.net submenu default -->
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="UTF-8" indent="no"
 omit-xml-declaration="yes"  media-type="text/html"/>

<xsl:param name="structure" select="defaultvalue"/>
<xsl:param name="content" select="defaultvalue"/>

<xsl:template match="/">
        <xsl:apply-templates/>
</xsl:template>

<xsl:template match="menu">
    <ul>
        <xsl:apply-templates select="item"/>
    </ul>
</xsl:template>

<xsl:template match="item">
    <xsl:variable name="level" select="count(ancestor::menu)"/>
    <!-- How many maximum tree elements to show -->
    <xsl:variable name="maxlevels" select="10"/>
    <xsl:variable name="minlevel" select="0"/>

    <!-- OTHER XML elements:
    "nameenc" -> element name, url encoded
    "lead" -> structure element intro text
    -->

    <li><a href="{@link}"><xsl:value-of select="name"/></a></li>

    <!-- PROCESS SUBLEVELS, if found, and no more THAN maxlevels, show only active sublevels -->
     <xsl:choose>
         <xsl:when test="$level &lt; $maxlevels">

         <xsl:choose>
             <xsl:when test="count(item) > 0">
                <ul>
                <xsl:apply-templates select="item"/>
                </ul>
            </xsl:when>
         </xsl:choose>

        </xsl:when>
     </xsl:choose>
     <!-- END PROCESS SUBLEVELS -->

</xsl:template>

</xsl:stylesheet>