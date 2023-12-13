USE [otcs]
GO

DECLARE @RC int

/* Get all docs within folders */
EXECUTE @RC = [dbo].[sp_GetLibMetadata] 20000, 144, 0;

/* Get all compound docs within folders */
EXECUTE @RC = [dbo].[sp_GetLibMetadataB] 20000, 136, 0;

/* Get all docs within compound docs */
EXECUTE @RC = [dbo].[sp_GetLibMetadata] 20000, 144, 136;

/* Get all physical docs within folders */
EXECUTE @RC = [dbo].[sp_GetLibMetadataB] 20000, 411, 0;

/* Get all folders within folders */
EXECUTE @RC = [dbo].[sp_GetLibMetadataB] 20000, 0, 0;

/* Get all virtual folders within folders */
EXECUTE @RC = [dbo].[sp_GetLibMetadataB] 20000, 899, 0;

/* Get all shortcuts within folders */
EXECUTE @RC = [dbo].[sp_GetLibMetadataB] 20000, 1, 0;

GO

