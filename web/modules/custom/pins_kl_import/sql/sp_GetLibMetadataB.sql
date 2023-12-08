USE [otcs]
GO

/****** Object:  StoredProcedure [dbo].[sp_GetLibMetadataB]    Script Date: 07/12/2023 15:02:34 ******/
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
	  '"' + substring(REPLACE(REPLACE([dbo].[GET_LIB_PATH] (t1.ParentId),'Enterprise|',''),'"','""'),1,250) + '"' as Folders,
	  '"' + substring(REPLACE(REPLACE([dbo].[GET_LIB_PATH] (t1.ParentId),'Enterprise|',''),'"','""'),251,250) + '"' as FoldersB,
	  '"' + substring(REPLACE(REPLACE([dbo].[GET_LIB_PATH] (t1.ParentId),'Enterprise|',''),'"','""'),501,250) + '"' as FoldersC,
	  '"' + substring(REPLACE(t1.Name,'"','""'),1,250) + '"' as Name,
	  '"' + substring(REPLACE(t1.Name,'"','""'),251,250) + '"' as NameB,
	  '"' + substring(REPLACE(t1.Name,'"','""'),501,250) + '"' as NameC,
	  t1.SubType as SubType,
	  t1.VersionNum as VersionNum,
	  lla.VerNum as VerNum,
	  isnull(t1.Ordering,0) as Ordering,
	  '"' + substring(REPLACE(CAST(t1.ExtendedData as NVARCHAR(MAX)),'"','""'),1,250) + '"' as ExtendedData,
	  '"' + substring(REPLACE(CAST(t1.ExtendedData as NVARCHAR(MAX)),'"','""'),251,250) + '"' as ExtendedDataB,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '^' + dbo.GET_LIB_PATH(cd.DataID) FROM LLClassify c inner join DTree cd on cd.DataID = c.CID
			  where c.ID = t1.DataID
			  FOR XML PATH (''))
			  ,1,1,''),''),'"','""'),1,250) + '"' as Classifications,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '^' + dbo.GET_LIB_PATH(cd.DataID) FROM LLClassify c inner join DTree cd on cd.DataID = c.CID
			  where c.ID = t1.DataID
			  FOR XML PATH (''))
			  ,1,1,''),''),'"','""'),251,250) + '"' as ClassificationsB,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '^' + dbo.GET_LIB_PATH(cd.DataID) FROM LLClassify c inner join DTree cd on cd.DataID = c.CID
			  where c.ID = t1.DataID
			  FOR XML PATH (''))
			  ,1,1,''),''),'"','""'),501,250) + '"' as ClassificationsC,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 26
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),1,250) + '"' AS ReadingLists,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 26
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),251,250) + '"' AS ReadingListsB,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 26
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),501,250) + '"' AS ReadingListsC,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 2
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),1,250) + '"' AS Series,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 2
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),251,250) + '"' AS SeriesB,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 2
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),501,250) + '"' AS SeriesC,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 25
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),1,250) + '"' AS Authors,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 25
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),251,250) + '"' AS AuthorsB,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData ll where ll.ID = t1.DataID and ll.VerNum = lla.VerNum and ll.AttrID = 25
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),501,250) + '"' AS AuthorsC
	from
	  DTree t1
	  inner join @LibTreeTempTable tt ON t1.DataID = tt.DataID
	  inner join LLAttrData lla on lla.ID = t1.DataID and lla.AttrID = 1 -- and t1.VersionNum = ll.VerNum
	  inner join DTree t2 ON t2.DataID = t1.ParentID
	where
	  t1.SubType = @subtype
	  --and lla.ValStr = 'Knowledge Document'
	  --and t1.SubType != 0
	  and t1.DataID not in(@Id, 2000)
	  and t2.SubType = @parentsubtype
	order by t1.DataID asc, t1.VersionNum asc
	;
END
GO


