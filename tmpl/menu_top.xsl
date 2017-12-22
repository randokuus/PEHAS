<!-- Modera.net submenu default -->
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="UTF-8" indent="no"
     omit-xml-declaration="yes"  media-type="text/html"/>

     <!-- levels of tree to render for all (active/inactive) items -->
    <!-- if set to 0 than all levels will be rendered -->
    <xsl:variable name="inactivemaxlevels" select="1"/>
    <!-- active levels of tree to render -->
    <!-- if 0 than all active items will be displayed -->
    <xsl:variable name="maxlevels" select="1"/>

    <xsl:param name="structure" select="defaultvalue"/>
    <xsl:param name="content" select="defaultvalue"/>
    <xsl:param name="language" select="defaultvalue"/>

    <xsl:template match="/">
    <div class="menu">
        <ul>
            <xsl:apply-templates/>
        </ul>
    </div>
    </xsl:template>


    <xsl:template match="item">

        <xsl:variable name="level" select="count(ancestor::item) + 1"/>
        <!-- How many maximum tree elements to show -->
        <xsl:variable name="maxlevels" select="1"/>

        <!-- CHECK active elements -->
        <xsl:variable name="active" select="
            (@structure = $structure)
            or starts-with($structure, concat(@structure, '.'))"/>
        <!-- END CHECK -->

        <!-- OTHER XML elements:
        "nameenc" -> element name, url encoded
        "lead" -> structure element intro text
        -->

        <xsl:choose>
            <xsl:when test="$active = 'active'">
                <li><a href="{@link}" class="active"><xsl:value-of select="name"/></a></li>
            </xsl:when>
            <xsl:otherwise>
                <li><a href="{@link}"><xsl:value-of select="name"/></a></li>
            </xsl:otherwise>
        </xsl:choose>

        <xsl:choose>
            <xsl:when test="
                0 = $inactivemaxlevels
                or $level &lt; $inactivemaxlevels
                or ((0 = $maxlevels or $level &lt; $maxlevels) and $active)">

                <xsl:if test="count(item) > 0">
                    <xsl:apply-templates select="item"/>
                </xsl:if>
            </xsl:when>
        </xsl:choose>

    </xsl:template>

</xsl:stylesheet>