<!-- This XSL style sheet is used during database initialization
     only if the database target server is MySQL.  -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output omit-xml-declaration="yes" indent="yes"/>

  <xsl:template match="@*|node()">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

  <!-- match all <field type="L"> elements and add
       the attribute 'size="1"' so that when used with
       MySQL the boolean is cast to TINYINT(1) and
       cakePHP automagic renders it to a checkbox -->
  <xsl:template match="field[@type='L']">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
      <xsl:attribute name="size">1</xsl:attribute>
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
