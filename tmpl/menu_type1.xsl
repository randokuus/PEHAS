<!-- Modera.net main menu sample XSL template 1 -->
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="UTF-8" indent="no"
 omit-xml-declaration="yes"  media-type="text/html"/>

<xsl:param name="structure" select="defaultvalue"/>
<xsl:param name="content" select="defaultvalue"/>
<xsl:param name="language" select="defaultvalue"/>

<xsl:template match="/">
<html><head></head>
<body>
<table border="1">
<xsl:apply-templates select="/menu/item"/>
</table>
</body>
</html>
</xsl:template>


<xsl:template match="item">
    <xsl:variable name="level" select="count(ancestor::item) + 1"/>
    <!-- How many maximum tree elements to show -->
    <xsl:variable name="maxlevels" select="3"/>
    <xsl:variable name="minlevel" select="1"/>

    <!-- CHECK active elements -->
    <xsl:variable name="active" select="
        (@structure = $structure)
        or starts-with($structure, concat(@structure, '.'))
        or $content = @content"/>
    <!-- END CHECK -->

    <xsl:choose>
         <xsl:when test="$level = 1">
        <tr>
            <td><a href="{@link}"><xsl:value-of select="name"/> (<xsl:value-of select="$active"/>) <xsl:value-of select="lead"/></a>
            </td>
        </tr>
        </xsl:when>
         <xsl:when test="$level = 2">
        <tr>
            <td>----<a href="{@link}"><xsl:value-of select="name"/> (<xsl:value-of select="$active"/>)</a>
            </td>
        </tr>
        </xsl:when>
         <xsl:when test="$level = 3">
        <tr>
            <td>--------<a href="{@link}"><xsl:value-of select="name"/> (<xsl:value-of select="$active"/>)</a>
            </td>
        </tr>
        </xsl:when>
    </xsl:choose>

    <!-- PROCESS SUBLEVELS, if found, and no more THAN maxlevels, show only active sublevels -->
     <xsl:choose>
<!--         <xsl:when test="$level &lt; $maxlevels and $active = 'active'">-->
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

</xsl:stylesheet>