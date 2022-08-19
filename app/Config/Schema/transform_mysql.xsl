<!-- This XSL style sheet is used during database initialization
     only if the database target server is MySQL.  -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output omit-xml-declaration="yes" indent="yes"/>

  <xsl:template match="@*|node()">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

  <!-- MySQL uses the ` (back tick) escaping character for reserved
       words. We will wrap all column names of type C to into back ticks.
       The 'char' columns are represented by one level xml elements -->
  <!-- Column name -->
  <xsl:template match="field[@type='C']">
    <xsl:copy>
      <xsl:apply-templates select="@*"/>
      <xsl:attribute name="name">`<xsl:value-of select="@name" />`</xsl:attribute>
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

  <!-- Index column name -->
  <xsl:template match="col">
    <col>`<xsl:value-of select="text()"/>`</col>
  </xsl:template>
</xsl:stylesheet>
