USE [otcs]
GO

/****** Object:  StoredProcedure [dbo].[sp_GetLibMetadataB]    Script Date: 11/12/2023 17:48:36 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO






CREATE PROCEDURE [dbo].[sp_GetLibMetadataB] (@numrecs integer = 20000, @subtype integer = 144, @parentsubtype integer = 0)
AS
BEGIN

	DECLARE @Id int = 18123764
	DECLARE @LibTreeTempTable TABLE
	(
	   DataID int,
	   ParentID int,
	   Name nvarchar(248),
	   SubType int
	)
	SET NOCOUNT ON;

	;WITH cte AS
	 (
	  SELECT a.DataID, a.ParentID, a.Name, a.SubType
	  FROM DTree a
	  WHERE DataID = @Id
	  UNION ALL
	  SELECT a.DataID, a.ParentID, a.Name, a.SubType
	  FROM DTree a JOIN cte c ON a.ParentID = c.DataID
	  )
	  INSERT INTO @LibTreeTempTable(DataID, ParentID, Name, SubType)
	  SELECT DataID, ParentID, Name, SubType
	  FROM cte;

	  --select * from @LibTreeTempTable;


	select top (@numrecs)
	  t1.DataID as DataID,
	  t1.ParentID as ParentID,
	  t2.SubType as ParentSubType,
	  isnull(t1.OriginDataID, 0) as OriginDataID,
	  REPLACE([dbo].[GET_LIB_PATH] (t1.ParentId),'Enterprise|','') as Folders,
	  t1.Name as Name,
	  t1.SubType as SubType,
	  t1.VersionNum as VersionNum,
	  isnull(lla.VerNum,1) as VerNum,
	  isnull(t1.Ordering,0) as Ordering,
	  isnull(t1.ExtendedData,'') as ExtendedData,
	  isnull(STUFF(
			 (SELECT '^' + dbo.GET_LIB_PATH(cd.DataID) FROM LLClassify c inner join DTree cd on cd.DataID = c.CID
			  where c.ID = t1.DataID
			  FOR XML PATH ('')),1,1,''),'') as Classifications,
	  isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 26
			  FOR XML PATH (''))
			  , 1, 1, ''),'') AS ReadingLists,
	  isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 2
			  FOR XML PATH ('')), 1, 1, ''),'') AS Series,
	  isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 25
			  FOR XML PATH ('')), 1, 1, ''),'') AS Authors,
	  isnull(STUFF(
			 (SELECT '|' + cast(ValLong as nvarchar(max)) from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 20
			  FOR XML PATH ('')), 1, 1, ''),'') AS Notes,
	  isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 6
			  FOR XML PATH ('')), 1, 1, ''),'') AS AltTitle
	from
	  DTree t1
	  inner join @LibTreeTempTable tt ON t1.DataID = tt.DataID
	  left join LLAttrData lla on lla.ID = t1.DataID and lla.AttrID = 1
	  inner join DTree t2 ON t2.DataID = t1.ParentID
	where
	  t1.SubType = @subtype
	  and t1.DataID not in(@Id, 2000)
	  and t2.SubType = @parentsubtype
	order by t1.DataID asc, t1.VersionNum asc
	;
END
GO


