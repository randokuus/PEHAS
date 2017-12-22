<!-- Modera.net submenu default -->
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="UTF-8" indent="no"
 omit-xml-declaration="yes"  media-type="text/html"/>

<xsl:variable name="inactivemaxlevels" select="1"/>
<xsl:variable name="maxlevels" select="4"/>

<xsl:param name="structure" select="defaultvalue"/>
<xsl:param name="content" select="defaultvalue"/>
<xsl:param name="language" select="defaultvalue"/>

<xsl:template match="/">
    <xsl:if test="count(/menu/item[@structure=$structure]) = 0
        or count(/menu/item[@structure=$structure]/item) > 0">
        <xsl:apply-templates select="/menu/item"/>
    </xsl:if>
</xsl:template>

<xsl:template match="item">
    <xsl:variable name="level" select="count(ancestor::item) + 1"/>
    <xsl:variable name="maxlevels" select="4"/>
    <xsl:variable name="minlevel" select="1"/>


    <xsl:variable name="active" select="
        (@structure = $structure)
        or starts-with($structure, concat(@structure, '.'))"/>

    <xsl:variable name="active2">
        <xsl:choose>
            <xsl:when test="$active = 'active' and count(menu/*) > 0">-open</xsl:when>
            <xsl:when test="$active = 'active' and count(menu/*) = 0" >-active</xsl:when>
            <xsl:otherwise></xsl:otherwise>
        </xsl:choose>
    </xsl:variable>

    <xsl:choose>
        <xsl:when test="$level = 3">
            <xsl:if test="position() = 1">
                <xsl:text disable-output-escaping="yes"><![CDATA[<div class="menu2"><ul>]]></xsl:text>
            </xsl:if>
            <xsl:choose>
                <xsl:when test="$active = 'active'">
                    <li><a href="{@link}" class="active"><xsl:value-of select="name"/></a></li>
                </xsl:when>
                <xsl:otherwise>
                    <li><a href="{@link}"><xsl:value-of select="name"/></a></li>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:if test="position() = last()">
                <xsl:text disable-output-escaping="yes"><![CDATA[</ul></div>]]></xsl:text>
            </xsl:if>
        </xsl:when>
    </xsl:choose>

    <!-- PROCESS SUBLEVELS, if found, and no more THAN maxlevels, show only active sublevels -->
     <xsl:choose>
         <xsl:when test="0 = $inactivemaxlevels
                or $level &lt; $inactivemaxlevels
                or ((0 = $maxlevels or $level &lt; $maxlevels) and $active)">
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