<!-- This XSL style sheet is used during database initialization
     only if the database target server is MySQL.  -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output omit-xml-declaration="yes" indent="yes"/>

    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>

    <!-- Partition is a reserved word for Postgresql -->
    <!-- We use the backtick even thought postgresql needs double quotes. This is how adodb works. -->
    <!-- Adodb will identify that this word needs to be quoted and will pick the database appropriate -->
    <!-- quotes from the configuration -->
    <xsl:template match="field[@name='partition']">
        <xsl:copy>
            <xsl:apply-templates select="@*"/>
            <xsl:attribute name="name">`<xsl:value-of select="@name" />`</xsl:attribute>
        </xsl:copy>
    </xsl:template>

    <!-- Index column name -->
    <xsl:template match="col[contains(., '(') and contains(., ')')]">
        <xsl:variable name="text" select="." />
        <col>
            <xsl:value-of select="substring-before($text, '(')" />
        </col>
    </xsl:template>
</xsl:stylesheet>
