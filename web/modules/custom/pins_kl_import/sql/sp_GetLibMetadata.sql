USE [otcs]
GO

/****** Object:  StoredProcedure [dbo].[sp_GetLibMetadata]    Script Date: 06/12/2023 07:45:19 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE PROCEDURE [dbo].[sp_GetLibMetadata] (@numrecs integer = 10000, @subtype integer = 144, @parentsubtype integer = 0)
AS
BEGIN
	SET NOCOUNT ON;

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
	  isnull(t1.Ordering,0) as Ordering,
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
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 26
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),1,250) + '"' AS ReadingLists,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 26
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),251,250) + '"' AS ReadingListsB,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 26
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),501,250) + '"' AS ReadingListsC,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 2
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),1,250) + '"' AS Series,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 2
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),251,250) + '"' AS SeriesB,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 2
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),501,250) + '"' AS SeriesC,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 25
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),1,250) + '"' AS Authors,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 25
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),251,250) + '"' AS AuthorsB,
	  '"' + substring(REPLACE(isnull(STUFF(
			 (SELECT '|' + ValStr from LLAttrData where ID = t1.DataID and VerNum = t1.VersionNum and AttrID = 25
			  FOR XML PATH (''))
			  , 1, 1, ''),''),'"','""'),501,250) + '"' AS AuthorsC
	from
	  DTree t1
	  inner join LLAttrData ll on ll.ID = t1.DataID and ll.AttrID = 1 and t1.VersionNum = ll.VerNum
	  inner join DTree t2 ON t2.DataID = t1.ParentID
	where
	  t1.SubType = @subtype
	  and ll.ValStr = 'Knowledge Document'
	  and t1.SubType != 0
	  and t1.DataID not in(18123764, 2000)
	  and t2.SubType = @parentsubtype
	--order by Folders
	;
END
GO
