USE [otcs]
GO

/****** Object:  UserDefinedFunction [dbo].[GET_LIB_PATH]    Script Date: 30/11/2023 13:35:47 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO



CREATE FUNCTION [dbo].[GET_LIB_PATH] ( @ID int )
RETURNS nvarchar(1024)
AS
BEGIN
	DECLARE @PID int
	DECLARE @RetVal nvarchar(1024)

	SET @PID = 0
	SET @RetVal = NULL

	WHILE @ID != -1
	BEGIN
        IF @RETVAL IS NULL BEGIN
            SELECT @PID=[ParentID], @RetVal = [Name]
		    FROM Dtree
		    WHERE DataID=ABS(@ID)
        END
		ELSE BEGIN
            SELECT @PID=[ParentID], @RetVal = [Name] + '|' + @RetVal
		    FROM Dtree
		    WHERE DataID=ABS(@ID)
        END

		SET @ID = @PID
	END

	RETURN @RetVal
END
GO

